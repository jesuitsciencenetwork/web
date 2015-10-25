<?php

namespace AppBundle;

use AppBundle\DTO\Position;
use Gregwar\Cache\Cache;

class Geocoder
{
    private $cache;

    private $replacementMap = array(
        'Dobrzyń Land' => 'Dobrzyń nad Wisłą',
    );

    private $manualLookup = array(
        'Samogitia' => array(55.75, 22.75),
        'Kashubia' => array(54.25, 18.00),
        'Red Ruthenia' => array(49.59, 24.41),
    );

    public function __construct($cacheDir)
    {
        $this->cache = new Cache($cacheDir);
    }

    /**
     * @param $placeName
     * @return Position
     */
    public function geocode($placeName)
    {
        if (array_key_exists($placeName, $this->manualLookup)) {
            list($lat, $lng) = $this->manualLookup[$placeName];
            return new Position($lat, $lng);
        }

        if (array_key_exists($placeName, $this->replacementMap)) {
            $placeName = $this->replacementMap[$placeName];
        }

        $json = $this->cache->getOrCreate(
            md5($placeName) . '.json',
            array(),
            function ($filename) use ($placeName) {
                $json = $this->fetch($placeName);
                file_put_contents($filename, $json);
            }
        );

        $data = json_decode($json, true);

        if ($data['status'] !== 'OK') {
            throw new \RuntimeException('Could not geocode: '.$placeName);
        }

        $loc = $data['results'][0]['geometry']['location'];
        return new Position($loc['lat'], $loc['lng']);
    }

    protected function fetch($placeName)
    {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json';

        $options = array(
            'language' => 'en',
            'key' => Constants::MAPS_API_KEY,
            'address' => $placeName
        );

        $url = $url . '?' . http_build_query($options);

        return file_get_contents($url);
    }
}
