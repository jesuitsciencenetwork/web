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
        return array_unique($matches[2]);
    }
}
