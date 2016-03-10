<?php

namespace AppBundle\DTO;

class LatLng
{
    private $lat;

    private $lng;

    /**
     * LatLng constructor.
     * @param $lat
     * @param $lng
     */
    public function __construct($lat, $lng)
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    /**
     * @return mixed
     */
    public function getLatitude()
    {
        return $this->lat;
    }

    /**
     * @return mixed
     */
    public function getLongitude()
    {
        return $this->lng;
    }
}
