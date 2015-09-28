<?php

namespace AppBundle\Viaf\RdfProvider;

use AppBundle\Viaf\RdfProviderInterface;
use Gregwar\Cache\Cache;

class CachingProvider implements RdfProviderInterface
{
    private $cache;
    private $originalProvider;

    public function __construct(RdfProviderInterface $originalProvider, $cacheDir)
    {
        $this->originalProvider = $originalProvider;
        $this->cache = new Cache($cacheDir);
    }

    public function getRdf($viaf)
    {
        $originalProvider = $this->originalProvider;
        return $this->cache->getOrCreate($viaf . '.xml', array(), function ($filename) use ($originalProvider, $viaf) {
            file_put_contents($filename, $originalProvider->getRdf($viaf));
        });
    }

}
