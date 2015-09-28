<?php

namespace AppBundle\Pdr;

interface IdiProviderInterface
{
    public function getXml($pdrId);
}
