<?php

namespace AppBundle\Pdr;

class IdProvider
{
    public function getIds()
    {
        return array(
            'pdrPo.001.042.000000001',
            'pdrPo.001.042.000000183',
            'pdrPo.001.042.000000289',
            'pdrPo.001.042.000000037'
        );

        // @todo find project lastmod, if no refresh necessary, then return array();
        // else: get idrange call, parse out ids

//        $url = 'https://pdrprod.bbaw.de/idi/pdr/'.$pdrId;
//
//        $ch = curl_init($url);
//        curl_setopt_array($ch, array(
//            CURLOPT_RETURNTRANSFER => true,
//            CURLOPT_HEADER => false
//        ));
//        $response = curl_exec($ch);
//
//        if (!$response) {
//            throw new \RuntimeException('Could not fetch "'.$url.'"');
//        }
//
//        return $response;
    }
}
