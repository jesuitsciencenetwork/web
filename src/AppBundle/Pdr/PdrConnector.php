<?php

namespace AppBundle\Pdr;

use AppBundle\Geocoder;

class PdrConnector
{
    private $idiProvider;
    private $geocoder;

    public function __construct(IdiProviderInterface $idiProvider, Geocoder $geocoder)
    {
        $this->geocoder = $geocoder;
        $this->idiProvider = $idiProvider;
    }

    public function processIdi($pdrId)
    {
        $source = $this->idiProvider->getXml($pdrId);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($source);

        if (false === $xml) {
            throw new \RuntimeException('Could not parse XML');
        }

        $data = array(
            'pdrId' => $pdrId,
            'displayName' => null,
            'viaf' => null,
            'beginningOfLife' => null,
            'endOfLife' => null,
            'sources' => array(),
            'aspects' => array(),
            'alternateNames' => array()
        );

//        $root = $xml->result[0];
//        $person = $root->person[0];

        $xml->registerXPathNamespace('po', 'http://pdr.bbaw.de/namespaces/podl/');
        $xml->registerXPathNamespace('ro', 'http://www.loc.gov/mods/v3');
        $xml->registerXPathNamespace('ao', 'http://pdr.bbaw.de/namespaces/aodl/');

        // get viaf
        foreach ($xml->xpath('//po:person/*/po:identifier') as $identifier) {
            $provider = (string)($identifier['provider']);
            if ('VIAF' !== $provider) {
                continue;
            }
            $data['viaf'] = (string)$identifier;
        }

        foreach ($xml->xpath('//ao:aspect') as $aspect) {
            $aspectData = array(
                'aoId' => (string)$aspect['id'],
                'type' => null,
                'placeName' => null,
                'lat' => null,
                'lng' => null,
                'description' => null,
            );

            $semantics = (string)$aspect->semanticDim->semanticStm;

            switch ($semantics) {
                case 'NormName_DE':
                    list($firstName, $lastName) = $this->getNamesFromNotification($aspect->notification);
                    $data['firstName'] = $firstName;
                    $data['lastName'] = $lastName;
                    continue 2;
                case 'Name':
                    $data['alternateNames'][] = $this->getSortedNameFromNotification($aspect->notification);
                    continue 2;
                case 'biographicalData':
                    $date = $aspect->notification->date;
                    $dateType = (string)$date['type'];
                    if ('event' != $dateType && $dateValue = (string)$date['when']) {
                        $data[$dateType] = $dateValue;
                    }

                    // parse date into $data
                    // birthdate/deathdate
                    // entryInTheOrder/expulsionFromTheOrder/resignationFromTheOrder

                    // do not break, also include full aspect!
            }

            $aspectData['type'] = $semantics;
            // @todo preserve links between stuff

            $aspectData['description'] = trim(strip_tags($aspect->notification->asXML()));

            // collect tags
            /** @var \SimpleXMLElement $notification */
            $notification = $aspect->notification;

            foreach ($notification->children() as $child) {

                switch ($child->getName()) {
                    case 'name':
                        // type of aspect!
                        break;
                    case 'persName':
                        // store reference, skip self-ref
                        break;
                    case 'placeName':
                        // geocode, then store
                        $placeName = (string)$child;
                        $position = $this->geocoder->geocode($placeName);
                        $aspectData['placeName'] = $placeName;
                        $aspectData['lat'] = $position->getLatitude();
                        $aspectData['lng'] = $position->getLongitude();
                        break;
                }
            }
//            var_dump((string)($aspect->notification));

            // relationStm
            // semanticStm
            // do not import: validationStm/reference

            $data['aspects'][] = $aspectData;
        }

        // collect sources
        foreach ($xml->xpath('//ro:mods') as $mods) {
            $data['sources'][] = (string)$mods['ID'];//$mods->asXML();
        }

        return $data;
    }

    protected function getSortedNameFromNotification($xml)
    {
        $first = $middle = $last = array();

        foreach ($xml->persName as $namePart) {
            $type = (string)$namePart['type'];
            $subType = (string)$namePart['subtype'];
            $value = (string)$namePart;

            if ('surname' === $type) {
                $part = 'last';
            } elseif ('forename' === $type && 'middle' == $subType) {
                $part = 'middle';
            } elseif ('forename' === $type) {
                $part = 'first';
            } else {
                throw new \RuntimeException('Unknown namepart: ' . $type . ' / ' . $subType);
            }

            array_push($$part, $value);
        }
        $names = array_merge($first, $middle, $last);
        return trim(implode(" ", $names));
    }

    protected function getNamesFromNotification($xml)
    {
        $first = $middle = $last = array();

        foreach ($xml->persName as $namePart) {
            $type = (string)$namePart['type'];
            $subType = (string)$namePart['subtype'];
            $value = (string)$namePart;

            if ('surname' === $type) {
                $part = 'last';
            } elseif ('forename' === $type && 'middle' == $subType) {
                $part = 'middle';
            } elseif ('forename' === $type) {
                $part = 'first';
            } else {
                throw new \RuntimeException('Unknown namepart: ' . $type . ' / ' . $subType);
            }

            array_push($$part, $value);
        }
        $firstNames = array_merge($first, $middle);
        return array(
            trim(implode(" ", $firstNames)),
            trim(implode(" ", $last))
        );
    }

    public function getIdRange()
    {

    }
}
