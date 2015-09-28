<?php

namespace AppBundle\Viaf;

class RdfParser
{
    private $xml;

    public function __construct($rdfContent)
    {
//        libxml_use_internal_errors(true);
//        $this->xml = simplexml_load_string($rdfContent);
//
//        if (false === $this->xml) {
//            $e = '';
//            foreach(libxml_get_errors() as $error) {
//                $e .= $error->message . "\n";
//            }
//            throw new \RuntimeException("Could not parse RDF data:\n" . $e);
//        }

        $this->xml = $rdfContent;
    }

    public function getAlternateNames()
    {

    }
}
