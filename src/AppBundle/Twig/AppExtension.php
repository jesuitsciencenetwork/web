<?php

namespace AppBundle\Twig;

use AppBundle\SearchService;
use AppBundle\StatsProvider;
use AppBundle\Twig\Helper\RenderingHelper;
use JoliTypo\Fixer;

class AppExtension extends \Twig_Extension
{
    /** @var Fixer */
    private $fixer;

    /** @var RenderingHelper */
    private $helper;

    /** @var StatsProvider */
    private $statsProvider;

    /** @var SearchService $search */
    private $search;

    private static $forceUppercase = [
        'Arabic',
        'Aristotelian logic',
        'Catalan',
        'Chinese writing system',
        'Czech',
        'English',
        'French',
        'German',
        'Greek',
        'Hebrew',
        'Infima',
        'Italian',
        'Latin',
        'Latin poetry',
        'Portuguese',
        'Russian',
        'Spanish',
        'Turkish',
    ];

    public function __construct(SearchService $search, RenderingHelper $helper, StatsProvider $provider)
    {
        $this->search = $search;
        $this->helper = $helper;
        $this->fixer = new Fixer(
            ['Ellipsis', 'Dash', 'EnglishQuotes', 'CurlyQuote']
        );
        $this->statsProvider = $provider;
    }
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('unset', [$this, 'unsetArray']),
            new \Twig_SimpleFilter('bit_add', [$this, 'addBit']),
            new \Twig_SimpleFilter('bit_remove', [$this, 'removeBit']),
            new \Twig_SimpleFilter('str_add', [$this, 'addString']),
            new \Twig_SimpleFilter('str_remove', [$this, 'removeString']),
            new \Twig_SimpleFilter('smart_quotes', [$this, 'smartQuotes'], [
                'is_safe' => ['html']
            ]),
            new \Twig_SimpleFilter('replace_links', [$this->helper, 'renderDescription'], [
                'is_safe' => ['html']
            ]),
            new \Twig_SimpleFilter('format_country', ['AppBundle\Helper', 'formatCountry']),
            new \Twig_SimpleFilter('slugify', ['Helper', 'slugify']),
            new \Twig_SimpleFilter('format_continent', ['AppBundle\Helper', 'formatContinent']),
            new \Twig_SimpleFilter('lcfirst', 'lcfirst'),
        ];
    }

    public function getTests()
    {
        return [
            new \Twig_SimpleTest('lowercaseable', [$this, 'lowercaseable']),
        ];
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('stats', [$this->statsProvider, 'get']),
            new \Twig_SimpleFunction('subjectGroupTree', [$this->search, 'getSubjectGroupTree']),
        ];
    }
    public function smartQuotes($value)
    {
        return $this->fixer->fix($value);
    }
    public function getName()
    {
        return 'app';
    }

    public function unsetArray($array, $key)
    {
        if (!is_array($key)) {
            $key = [$key];
        }

        foreach ($key as $k) {
            unset($array[$k]);
        }

        return $array;
    }

    public function addString($params, $key, $str)
    {
        if (array_key_exists($key, $params)) {
            $items = explode(' ', $params[$key]);
        } else {
            $items = array();
        }

        $items[] = $str;
        $items = array_unique($items);

        $params[$key] = implode(' ', $items);

        return $params;
    }

    public function removeString($params, $key, $str)
    {
        if (!array_key_exists($key, $params)) {
            return $params;
        }

        $items = explode(' ', $params[$key]);
        $items = array_filter($items, function ($i) use ($str) {
            return $i != $str;
        });
        $params[$key] = implode(' ', $items);

        return $params;
    }

    public function addBit($params, $key, $bit)
    {
        $params[$key] = $params[$key] | $bit;
        return $params;
    }

    public function removeBit($params, $key, $bit)
    {
        $params[$key] = $params[$key] & ~$bit;
        return $params;
    }

    public function lowercaseable($value)
    {
        if (in_array($value, self::$forceUppercase)) {
            return false;
        }

        return true;
    }
}
