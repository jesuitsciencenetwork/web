<?php

namespace AppBundle\Pdr;

use AppBundle\Helper;

class IdProvider
{
    public function getIds()
    {
        // @todo find project lastmod, if no refresh necessary, then return array();
        // else: get idrange call, parse out ids

        $url = 'https://pdrprod.bbaw.de/axis2/services/Utilities/getOccupiedIDRanges?Type=pdrPo&Instance=1&Min=1&Max=99999999&Project=42';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false
        ]
        );
        $response = curl_exec($ch);

        if (!$response) {
            throw new \RuntimeException('Could not fetch "'.$url.'"');
        }

        return $this->getIdsFromXml($response);
    }

    private function getIdsFromXml($responseText)
    {
        $xml = simplexml_load_string($responseText);

        $xml->registerXPathNamespace('allies', 'http://allies.pdr.bbaw.org');

        $ids = [];
        foreach ($xml->xpath('//allies:Range') as $range) {
            $info = $range->children('http://allies.pdr.bbaw.org');
            $ids = array_merge($ids, range((int)((string)$info->Min), (int)((string)$info->Max)));
        }

        return array_map(function($e) {
            return Helper::num2pdr($e);
        }, $ids);
    }
}
