<?php

namespace AppBundle\DTO;

use Symfony\Component\HttpFoundation\ParameterBag;

class Radius
{
    /** @var Location */
    private $center;

    /** @var int */
    private $radius;

    /**
     * Radius constructor.
     * @param Location $center
     * @param int $radius
     */
    public function __construct(Location $center, $radius)
    {
        $this->center = $center;
        $this->radius = $radius;
    }

    public static function fromQuery(ParameterBag $q)
    {
        $loc = new Location($q->get('lat'), $q->get('lng'), 'XX');
        $loc->setDescription($q->get('placeName'));
        return new Radius($loc, $q->get('radius'));
    }

    /**
     * @return Location
     */
    public function getCenter()
    {
        return $this->center;
    }

    /**
     * @param Location $center
     */
    public function setCenter($center)
    {
        $this->center = $center;
    }

    /**
     * @return int
     */
    public function getRadius()
    {
        return $this->radius;
    }

    /**
     * @param int $radius
     */
    public function setRadius($radius)
    {
        $this->radius = $radius;
    }
}
