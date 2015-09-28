<?php

namespace AppBundle\Pdr\IdiProvider;

use AppBundle\Pdr\IdiProviderInterface;

class RemoteProvider implements IdiProviderInterface
{
    public function getXml($pdrId)
    {
        $url = 'https://pdrprod.bbaw.de/idi/pdr/'.$pdrId;

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false
        ));
        $response = curl_exec($ch);

        if (!$response) {
            throw new \RuntimeException('Could not fetch "'.$url.'"');
        }

        return $response;
    }
}
