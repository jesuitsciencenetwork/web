<?php

namespace AppBundle\DTO;

class Position
{
    private $lat;
    private $lng;
    private $country;

    public function __construct($lat, $lng, $country)
    {
        $this->lat = $lat;
        $this->lng = $lng;
        $this->country = $country;
    }

    public function getLatitude()
    {
        return $this->lat;
    }

    public function getLongitude()
    {
        return $this->lng;
    }

    public function getCountry()
    {
        return $this->country;
    }
}
