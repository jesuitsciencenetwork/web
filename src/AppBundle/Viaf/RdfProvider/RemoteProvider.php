<?php

namespace AppBundle\Viaf\RdfProvider;

use AppBundle\Viaf\RdfProviderInterface;

class RemoteProvider implements RdfProviderInterface
{
    public function getRdf($viaf)
    {
        $url = 'http://viaf.org/viaf/'.$viaf.'/rdf.xml';

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
