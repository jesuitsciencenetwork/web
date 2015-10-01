<?php

namespace AppBundle\Viaf;

class ViafConnector
{
    /** @var RdfProviderInterface */
    private $rdfProvider;

    /**
     * ViafConnector constructor.
     * @param $rdfProvider
     */
    public function __construct(RdfProviderInterface $rdfProvider)
    {
        $this->rdfProvider = $rdfProvider;
    }

    public function getAlternateNames($viaf)
    {
        $rdf = $this->rdfProvider->getRdf($viaf);
        preg_match_all('=<schema:(alternateN|n)ame.*?>(.+?)</schema:=m', $rdf, $matches);
        $names = array_map(function($v) {
            return str_replace('"', '', html_entity_decode(str_replace(array('&#152;','&#156;', "\n", "\r"), '', $v), ENT_QUOTES | ENT_XML1, 'UTF-8'));
        }, $matches[2]);
        return array_unique($names);
    }
}
