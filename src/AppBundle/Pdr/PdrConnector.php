<?php

namespace AppBundle\Pdr;

use AppBundle\Geocoder;
use AppBundle\Helper;

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
            throw new \RuntimeException('Could not parse XML for Entry '. $pdrId);
        }

        $data = array(
            'pdrId' => $pdrId,
            'firstName' => null,
            'lastName' => null,
            'nameLink' => null,
            'title' => null,
            'viaf' => null,
            'beginningOfLife' => null,
            'endOfLife' => null,
            'sources' => array(),
            'aspects' => array(),
            'subjects' => array(),
            'personRefs' => array(),
            'nonjesuit' => false,
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
            );

            $semantics = (string)$aspect->semanticDim->semanticStm;

            switch ($semantics) {
                case 'residence':
                    continue 2;
                case 'relatives':
                    $semantics = 'miscellaneous';
                    break;
                case 'NormName_DE':
                    list($titles, $firstName, $nameLink, $lastName, $nonJesuit) = $this->getNamesFromNormNameNotification($aspect->notification);
                    $data['title'] = $titles;
                    $data['firstName'] = $firstName;
                    $data['nameLink'] = $nameLink;
                    $data['lastName'] = $lastName;
                    $data['nonjesuit'] = $nonJesuit;
                    continue 2;
                case 'Name':
                    $data['alternateNames'][] = $this->getSortedNameFromAlternateNameNotification($aspect->notification);
                    continue 2;
            }

            $aspectData['type'] = $semantics;
            // @todo preserve links between stuff

            $notificationData = $this->processNotification($aspect->notification, Helper::pdr2num($pdrId));
            $aspectData = array_merge($aspectData, $notificationData);
            foreach ($aspectData['subjects'] as $slug => $subject) {
                $data['subjects'][$slug] = $subject;
            }
            if ($aspectData['type'] == 'beginningOfLife') {
                $data['beginningOfLife'] = $aspectData['dateExact'];
            } elseif ($aspectData['type'] == 'endOfLife') {
                $data['endOfLife'] = $aspectData['dateExact'];
            }

            // relationStm
            foreach ($aspect->relationDim->relationStm as $relationStatement) {
                $rel = $relationStatement->relation[0];
                $class = (string)$rel['class'];
                $context = (string)$rel['context'];
                $value = (string)$rel;
                $subjectString = (string)$relationStatement['subject'];
                $subject = Helper::pdr2num($subjectString);
                $objectString = (string)$relationStatement->relation[0]['object'];
                $object = Helper::pdr2num($objectString);
                if ('aspectOf' === $value || false !== strpos($subjectString, 'pdrAo') || $subjectString != $pdrId) {
                    continue;
                }

                $data['personRefs'][] = array(
                    $subject,
                    $object,
                    $class,
                    $context,
                    $value
                );
            }

            // semanticStm
            // do not import: validationStm/reference

            $data['aspects'][] = $aspectData;
        }

        // collect sources
        foreach ($xml->xpath('//ro:mods') as $mods) {
            $data['sources'][] = (string)$mods['ID'];//$mods->asXML();
        }

        //$data['subjects'] = array_unique($data['subjects']);

        return $data;
    }

    protected function getSortedNameFromAlternateNameNotification($xml)
    {
        $titles = $first = $middle = $last = array();
        $nameLink = null;

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
            } elseif ('titleOfNobility' === $type) {
                $part = 'titles';
            } elseif ('nameLink' === $type) {
                $nameLink = $value;
                continue;
            } elseif ('pseudonym' === $type) {
                $part = 'middle';
            } else {
                throw new \RuntimeException('Unknown namepart: ' . $type . ' / ' . $subType);
            }

            array_push($$part, $value);
        }
        $firstNames = array_merge($titles, $first, $middle);
        return html_entity_decode(
            str_replace(
                array('&#152;','&#156;', "\n", "\r"),
                '',
                trim(implode(" ", $firstNames)) . ($nameLink ? (substr($nameLink, -1, 1) == "'" ? ' ' . $nameLink : ' ' . $nameLink . ' ') : ' ') . trim(implode(" ", $last))
            ),
            ENT_QUOTES | ENT_XML1,
            'UTF-8'
        );
    }

    protected function getNamesFromNormNameNotification($xml)
    {
        $titles = $first = $middle = $last = array();
        $nonJesuit = false;
        $nameLink = null;

        foreach ($xml->name as $comment) {
            $type = (string)$comment['type'];
            if ('Nonjesuit' == $type) {
                $nonJesuit = true;
            }
        }

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
            } elseif ('titleOfNobility' === $type) {
                $part = 'titles';
            } elseif ('nameLink' === $type) {
                $nameLink = $value;
                continue;
            } elseif ('pseudonym' === $type) {
                $part = 'middle'; // only occurs once: Benedykt Herbest, pdrPo.001.042.000000613
            } else {
                throw new \RuntimeException('Unknown namepart: ' . $type . ' / ' . $subType);
            }

            array_push($$part, $value);
        }
        $firstNames = array_merge($first, $middle);
        return array(
            trim(implode(" ", $titles)),
            trim(implode(" ", $firstNames)),
            $nameLink ? trim($nameLink) : $nameLink,
            trim(implode(" ", $last)),
            $nonJesuit
        );
    }

    public function getIdRange()
    {

    }

    private function processNotification($xml, $currentPoId)
    {
        $output = array(
            'dateFrom' => null,
            'dateTo' => null,
            'dateExact' => null,
            'placeName' => null,
            'lat' => null,
            'lng' => null,
            'country' => null,
            'subjects' => array(),
            'description' => '',
        );
        $textParts = array();
        $dom = dom_import_simplexml($xml);
        foreach ($dom->childNodes as $childNode) {
            if ($childNode->nodeType == XML_TEXT_NODE) {
                if (strpos(strtolower($childNode->nodeValue), 'death') !== false) {
                    $output['type'] = 'endOfLife';
                } elseif (strpos(strtolower($childNode->nodeValue), 'birth') !== false) {
                    $output['type'] = 'beginningOfLife';
                } elseif (strpos(strtolower($childNode->nodeValue), 'entry in the order') !== false) {
                    $output['type'] = 'entryInTheOrder';
                } elseif (strpos(strtolower($childNode->nodeValue), 'resignation') !== false) {
                    $output['type'] = 'resignationFromTheOrder';
                }
                $textParts[] = trim($childNode->nodeValue);
                continue;
            }

            $tag  = $childNode->nodeName;
            $type = $childNode->getAttribute('type');
            $subtype = $childNode->getAttribute('subtype');
            $href = $childNode->getAttribute('ana');

            $ana = Helper::pdr2num($href);
            if ($tag == 'persName') {
                $textParts[] = '{P:' . $ana . '|' . $childNode->nodeValue . '}';
            } elseif ($tag == 'name' && $type == 'science' && $subtype == 'subject') {
                if ($href && $ana !== $currentPoId) {
                    continue;
                }
                $slug = Helper::slugify($childNode->nodeValue);
                $output['subjects'][$slug] = $childNode->nodeValue;
                $textParts[] = '{S:' . $slug . '|' . $childNode->nodeValue . '}';
            } elseif ($tag == 'placeName') {
                $pos = $this->geocoder->geocode($childNode->nodeValue);

                $output['lat'] = $pos->getLatitude();
                $output['lng'] = $pos->getLongitude();
                $output['country'] = $pos->getCountry();
                $output['placeName'] = $childNode->nodeValue;

                $textParts[] = '{M:' . $pos->getLatitude(
                    ) . ',' . $pos->getLongitude(
                    ) . '|' . $childNode->nodeValue . '}';
            } elseif ($tag == 'name') {
                $textParts[] = $childNode->nodeValue;
            } elseif ($tag == 'date') {
                if ($type == 'event' && $subtype) {
                    $output['type'] = $subtype; // exit/resign/entry
                } elseif ($type == 'beginningOfLife' || $type == 'endOfLife') {
                    $output['type'] = $type;
                }

                if ($date = $childNode->getAttribute('when')) {
                    $output['dateExact'] = $date;
                }
                if ($date = $childNode->getAttribute('from')) {
                    $output['dateFrom'] = $date;
                }
                if ($date = $childNode->getAttribute('to')) {
                    $output['dateTo'] = $date;
                }
                $textParts[] = $childNode->nodeValue;
            } else {
                $textParts[] = $childNode->nodeValue;
//                var_dump($tag);
//                var_dump($type);
//                var_dump($subtype);
//                var_dump($href);
//                echo " == \n";
            }

        }

        //$output['subjects'] = array_unique($output['subjects']);

        $output['description'] = implode(' ', $textParts);

        return $output;
    }
}
