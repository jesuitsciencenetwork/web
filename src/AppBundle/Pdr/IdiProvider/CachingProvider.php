<?php

namespace AppBundle\Pdr\IdiProvider;

use AppBundle\Pdr\IdiProviderInterface;
use Gregwar\Cache\Cache;

class CachingProvider implements IdiProviderInterface
{
    private $cache;
    private $originalProvider;

    public function __construct(IdiProviderInterface $originalProvider, $cacheDir)
    {
        $this->originalProvider = $originalProvider;
        $this->cache = new Cache($cacheDir);
    }

    public function getXml($pdrId)
    {
        // todo: check against lm idi if refresh necessary
        $originalProvider = $this->originalProvider;
        return $this->cache->getOrCreate($pdrId . '.xml', [], function ($filename) use ($originalProvider, $pdrId) {
            file_put_contents($filename, $originalProvider->getXml($pdrId));
        });
    }

}
