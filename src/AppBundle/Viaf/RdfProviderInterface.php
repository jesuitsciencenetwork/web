<?php

namespace AppBundle\Viaf;

interface RdfProviderInterface
{
    public function getRdf($viaf);
}
