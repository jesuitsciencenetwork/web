<?php

namespace AppBundle\DTO;

class Bounds
{
    /** @var LatLng south-west corner  */
    private $sw;

    /** @var LatLng north-east corner */
    private $ne;

    /**
     * Construct Bounds object from url value
     *
     * Url value is a string of the form "lat_lo,lng_lo,lat_hi,lng_hi", where
     * "lo" corresponds to the southwest corner of the bounding box, while "hi"
     * corresponds to the northeast corner of that box
     */
    public static function fromUrlValue($string)
    {
        if (!preg_match('/^([\-+]?\d+\.\d+),([\-+]?\d+\.\d+),([\-+]?\d+\.\d+),([\-+]?\d+\.\d+)$/', $string, $matches)) {
            throw new \InvalidArgumentException('Invalid bounds definition');
        }

        return new self(new LatLng($matches[1], $matches[2]), new LatLng($matches[3], $matches[4]));
    }

    /**
     * Bounds constructor.
     * @param LatLng $sw
     * @param LatLng $ne
     */
    private function __construct(LatLng $sw, LatLng $ne)
    {
        $this->sw = $sw;
        $this->ne = $ne;
    }

    /**
     * @return LatLng
     */
    public function getSouthWest()
    {
        return $this->sw;
    }

    /**
     * @return LatLng
     */
    public function getNorthEast()
    {
        return $this->ne;
    }
}
