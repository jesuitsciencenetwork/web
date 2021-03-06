<?php

namespace AppBundle\Pdr;

use AppBundle\Helper;

class PdrConnector
{
    private $idiProvider;
    private $tz;

    public function __construct(IdiProviderInterface $idiProvider)
    {
        $this->idiProvider = $idiProvider;
        $this->tz = new \DateTimeZone('Europe/Berlin');
    }

    public function processIdi($pdrId)
    {
        $source = $this->idiProvider->getXml($pdrId);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($source);

        if (false === $xml) {
            throw new \RuntimeException('Could not parse XML for Entry '. $pdrId);
        }

        $data = [
            'pdrId' => $pdrId,
            'firstName' => null,
            'lastName' => null,
            'shortName' => null,
            'nameLink' => null,
            'title' => null,
            'viaf' => null,
            'beginningOfLife' => null,
            'endOfLife' => null,
            'sources' => [],
            'aspects' => [],
            'subjects' => [],
            'places' => [],
            'personRefs' => [],
            'nonjesuit' => false,
            'alternateNames' => []
        ];

//        $root = $xml->result[0];
//        $person = $root->person[0];

        $xml->registerXPathNamespace('po', 'http://pdr.bbaw.de/namespaces/podl/');
        $xml->registerXPathNamespace('ro', 'http://www.loc.gov/mods/v3');
        $xml->registerXPathNamespace('ao', 'http://pdr.bbaw.de/namespaces/aodl/');

        $maxDate = new \DateTime('0000-00-00T00:00:00', $this->tz);

        // get lastmod
        foreach ($xml->xpath('//ao:revision') as $rev) {
            $time = (string)$rev['timestamp'];
            if ($time) {
                $dt = new \DateTime($time, $this->tz);
                if ($dt > $maxDate) {
                    $maxDate = $dt;
                }
            }
        }

        $data['lastmod'] = $maxDate->format('Y-m-d H:i:s');

        // get viaf
        foreach ($xml->xpath('//po:person/*/po:identifier') as $identifier) {
            $provider = (string)($identifier['provider']);
            if ('VIAF' !== $provider) {
                continue;
            }
            $data['viaf'] = trim((string)$identifier);
        }

        foreach ($xml->xpath('//ao:aspect') as $aspect) {
            $aspectData = [
                'aoId' => (string)$aspect['id'],
                'type' => null,
            ];

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
            $data['places'] = array_merge($data['places'], $aspectData['places']);

            if ($aspectData['type'] == 'beginningOfLife') {
                $data['beginningOfLife'] = $aspectData['dateExact'];
            } elseif ($aspectData['type'] == 'endOfLife') {
                $data['endOfLife'] = $aspectData['dateExact'];
            }

            // relationStm
            foreach ($aspect->relationDim->relationStm as $relationStatement) {
                foreach ($relationStatement->relation as $rel) {
                    $class = (string)$rel['class'];
                    $context = (string)$rel['context'];
                    $value = (string)$rel;
                    $subjectString = (string)$relationStatement['subject'];
                    $subject = Helper::pdr2num($subjectString);
                    $objectString = (string)$rel['object'];
                    $object = Helper::pdr2num($objectString);
                    if ('aspectOf' === $value || false !== strpos($subjectString, 'pdrAo') || $subjectString != $pdrId) {
                        continue;
                    }

                    $data['personRefs'][] = [
                        $subject,
                        $object,
                        $class,
                        $context,
                        $value,
                        Helper::pdr2num($aspectData['aoId'])
                    ];
                }
            }

            // validationStm
            foreach ($aspect->validation->validationStm->reference as $ref) {
                $refText = (string)$ref;
                if (false === strpos($refText, 'pdrRo')) {
                    echo "found ref: $refText\n";
                }
                $aspectData['source'] = $refText;
            }

            $data['aspects'][] = $aspectData;
        }

        // collect sources
        foreach ($xml->xpath('//ro:mods') as $mods) {
            $data['sources'][(string)$mods['ID']] = $this->processMods($mods, $pdrId);
        }

        //$data['subjects'] = array_unique($data['subjects']);

        // process names
        $displayName = '';

        if ($data['title']) {
            $displayName .= $data['title'] . ' ';
        }
        if ($data['firstName']) {
            $displayName .= $data['firstName'] . ' ';
        }
        if ($data['nameLink']) {
            if (substr($data['nameLink'], -1, 1) == "'") {
                $displayName .= $data['nameLink'];
            } else {
                $displayName .= $data['nameLink'] . ' ';
            }
        }
        if ($data['lastName']) {
            $displayName .= $data['lastName'];
        }

        $data['displayName'] = trim($displayName);

        $listName = '';

        if ($data['nameLink']) {
            if (substr($data['nameLink'], -1, 1) == "'") {
                $listName .= $data['nameLink'];
            } else {
                $listName .= $data['nameLink'] . ' ';
            }
        }

        $shortName = $listName;

        if ($data['lastName']) {
            $listName .= $data['lastName'] . ', ';
            $shortName .= $data['lastName'];
        }

        if (!$shortName) {
            // popes and queens
            $shortName = $data['firstName'];
        }

        if ($data['title']) {
            $listName .= $data['title'] . ' ';
        }
        if ($data['firstName']) {
            $listName .= $data['firstName'] . ' ';
        }

        $data['listName'] = trim($listName);
        $data['shortName'] = $shortName;

        $nameForSorting = trim(strtoupper(Helper::removeAccents($data['lastName'] . ' ' . $data['firstName'])));
        $data['nameForSorting'] = $nameForSorting;
        $data['groupLetter'] = substr($nameForSorting, 0, 1);

        return $data;
    }

    protected function processMods($mods, $poId)
    {
        $captured = (string)$mods->originInfo->dateCaptured;
        $data = [
            'payload' => $poId,
            'title' => (string)$mods->titleInfo->title,
            'dateIssued' => (string)$mods->originInfo->dateIssued,
            'dateCaptured' => '0000' == $captured ? null : $captured,
            'place' => (string)$mods->originInfo->place->placeTerm,
            'note' => (string)$mods->note,
            'genre' => (string)$mods->genre,
            'url' => (string)$mods->location->url,
            'publisher' => (string)$mods->originInfo->publisher,
            'authors' => [],
            'editors' => [],
            'seriesTitle' => $mods->relatedItem ? (string)$mods->relatedItem->titleInfo->title : null,
        ];

        if ('VIAF' === $data['genre'] && false !== strpos($data['url'], '/gnd/')) {
            $data['genre'] = 'GND';
        }

        foreach ($mods->name as $name) {
            $role = (string)$name->role->roleTerm;
            $family = '';
            $given = '';
            foreach ($name->namePart as $namePart) {
                switch ((string)$namePart['type']) {
                    case 'given':
                        $given = (string)$namePart;
                        break;
                    case 'family':
                        $family = (string)$namePart;
                        break;
                    default:
                        throw new \Exception('Unknown namePart type in source: '.(string)$namePart['type']);
                }
            }
            switch ($role) {
                case 'edt':
                    $data['editors'][] = [$given, $family];
                    break;
                case 'aut':
                case 'prg': // Wikipedia entries tagged this way
                    $data['authors'][] = [$given, $family];
                    break;
                default:
                    throw new \Exception("Unknown role \"$role\" for source ".(string)$mods['ID']);
            }
        }

        $data = array_map(function($e) {
            return '' === $e ? null : $e;
        }, $data);

        if ($data['editors'] == [['Carlos', 'Sommervogel']]) {
            $data['group'] = 'sommervogel';
        } elseif ($data['genre'] == 'VIAF' || $data['genre'] == 'GND') {
            $data['group'] = 'viaf';
        } elseif ($data['publisher'] == 'Universidad Pontifica Comillas') {
            $data['group'] = 'dhcj';
        } elseif (strpos($data['url'], 'wikipedia.org') !== false) {
            $data['group'] = 'wp';
        } else {
            $data['group'] = null;
        }

        return $data;
    }

    protected function getSortedNameFromAlternateNameNotification($xml)
    {
        $titles = $first = $middle = $last = [];
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
                ['&#152;','&#156;', "\n", "\r"],
                '',
                trim(implode(" ", $firstNames)) . ($nameLink ? (substr($nameLink, -1, 1) == "'" ? ' ' . $nameLink : ' ' . $nameLink . ' ') : ' ') . trim(implode(" ", $last))
            ),
            ENT_QUOTES | ENT_XML1,
            'UTF-8'
        );
    }

    protected function getNamesFromNormNameNotification($xml)
    {
        $titles = $first = $middle = $last = [];
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
        return [
            trim(implode(" ", $titles)),
            trim(implode(" ", $firstNames)),
            $nameLink ? trim($nameLink) : $nameLink,
            trim(implode(" ", $last)),
            $nonJesuit
        ];
    }

    public function getIdRange()
    {

    }

    private function processNotification(\SimpleXMLElement $xml, $currentPoId)
    {
        $output = [
            'dateFrom' => null,
            'dateTo' => null,
            'dateExact' => null,
            'places' => [],
            'occupation' => null,
            'affiliation' => null,
            'subjects' => [],
            'comments' => [],
            'raw' => $xml->asXML()
        ];
        $textParts = [];
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
                $textParts[] = $childNode->nodeValue;
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
                $slug = Helper::slugify($childNode->nodeValue);
                $textParts[] = '{S:' . $slug . '|' . $childNode->nodeValue . '}';
                if (!$href || $ana === $currentPoId) {
                    $output['subjects'][$slug] = $childNode->nodeValue;
                }
            } elseif ($tag == 'name' && $type == 'occupation') {
                $output['occupation'] = ucfirst($childNode->nodeValue);
                $slug = Helper::slugify($childNode->nodeValue);
                $textParts[] = '{O:' . $slug . '|' . $childNode->nodeValue . '}';
            } elseif ($tag == 'name' && $type == 'Comment') {
                $output['comments'][] = $childNode->nodeValue;
                $textParts[] = $childNode->nodeValue;
            } elseif ($tag == 'placeName') {
                $output['places'][] = $childNode->nodeValue;
                $textParts[] = '{M:' . $childNode->nodeValue . '|' . $childNode->nodeValue . '}';
            } elseif ($tag == 'name') {
                $textParts[] = $childNode->nodeValue;
            } elseif ($tag == 'orgName') {
                $output['affiliation'] = $childNode->nodeValue;
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
//            } elseif ($tag == 'orgName' && $subtype == 'academy') { // @TODO

            } else {
                $textParts[] = $childNode->nodeValue;
            }

        }

        $output['description'] = implode('', $textParts);

        return $output;
    }
}
