<?php

namespace AppBundle;

use AppBundle\DTO\Location;
use Gregwar\Cache\Cache;
use Psr\Log\LoggerInterface;

/**
 * Geocodes places via Google Geocoder
 */
class Geocoder
{
    private $cache;
    private $logger;

    private $replacementMap = [
        'Dobrzyń Land' => 'Dobrzyń nad Wisłą',
        'Saint Petersburg' => 'Saint Petersburg, Russia',
        'Buda' => 'Budapest, Hungary',
        'Naples' => 'Naples, Italy',
        'Vilnius Voivodeship' => 'Vilnius, Lithuania',
        'Serpa' => 'Serpa, Portugal',
        'Segno' => 'Segno, Italy',
        'Gascony' => 'Mont-de-Marsan, France',
        'Clermont' => 'Clermont-Ferrand, France',
        'Nola' => 'Nola, Italy',
        'Transylvania' => 'Cluj-Napoca, Rumania',
        'East Prussia' => 'Kaliningrad, Russia',
        'North America' => 'Kansas City, USA',
        'Yorkshire' => 'Yorkshire, UK',
    ];

    private $manualLookup = [
        'Samogitia' => [55.75, 22.75, 'PL'],
        'Kashubia' => [54.25, 18.00, 'PL'],
        'Red Ruthenia' => [49.59, 24.41, 'PL'],
    ];

    private static $continentCodes = [
        'Europe' => 'EU',
        'Asia' => 'AS',
        'North America' => 'NA',
    ];

    public function __construct($cacheDir, LoggerInterface $logger)
    {
        $this->cache = new Cache($cacheDir);
        $this->logger = $logger;
    }

    /**
     * @param $placeName
     * @return Location
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    public function geocode($placeName)
    {
        if (array_key_exists($placeName, $this->manualLookup)) {
            list($lat, $lng, $country) = $this->manualLookup[$placeName];
            return new Location($lat, $lng, $country);
        }

        if (array_key_exists($placeName, $this->replacementMap)) {
            $placeName = $this->replacementMap[$placeName];
        }

        $json = $this->cache->getOrCreate(
            md5($placeName) . '.json',
            [],
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

        $country = null;
        foreach ($data['results'][0]['address_components'] as $comp) {
            if (!in_array('country', $comp['types'])) {
                continue;
            }
            $country = $comp['short_name'];
        }

        $continent = null;
        if (!$country) {
            foreach ($data['results'][0]['address_components'] as $comp) {
                if (!in_array('continent', $comp['types'])) {
                    continue;
                }
                if (!array_key_exists($comp['short_name'], self::$continentCodes)) {
                    throw new \Exception('Continent '.$comp['short_name'] . ' unknown');
                }
                $continent = self::$continentCodes[$comp['short_name']];
            }
        }

        if (!$country && !$continent) {
            //$this->logger->warning('Geocoding did not yield a country or continent for "{placeName}"', array('placeName'=>$placeName));
        }

        return new Location($loc['lat'], $loc['lng'], $country, $continent);
    }

    protected function fetch($placeName)
    {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json';

        $options = [
            'language' => 'en',
            'key' => Constants::MAPS_API_KEY,
            'address' => $placeName
        ];

        $url = $url . '?' . http_build_query($options);

        return file_get_contents($url);
    }
}
