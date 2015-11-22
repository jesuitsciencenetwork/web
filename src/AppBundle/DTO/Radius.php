<?php

namespace AppBundle\DTO;

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
