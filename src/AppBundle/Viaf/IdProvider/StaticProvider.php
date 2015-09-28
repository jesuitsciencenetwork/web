<?php

namespace AppBundle\Viaf\IdProvider;

use AppBundle\Viaf\IdProviderInterface;

class StaticProvider implements IdProviderInterface
{
    private $idList;

    public function __construct($idList)
    {
        $this->idList = $idList;
    }

    public function getIds()
    {
        return $this->idList;
    }
}
