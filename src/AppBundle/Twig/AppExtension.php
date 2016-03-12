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
            new \Twig_SimpleFilter('add_subject', [$this, 'addSubject']),
            new \Twig_SimpleFilter('remove_subject', [$this, 'removeSubject']),
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

    public function addSubject($params, $subject)
    {
        if (array_key_exists('subjects', $params)) {
            $subjects = explode(' ', $params['subjects']);
        } else {
            $subjects = array();
        }

        $subjects[] = $subject;
        $subjects = array_unique($subjects);

        $params['subjects'] = implode(' ', $subjects);

        return $params;
    }

    public function removeSubject($params, $subject)
    {
        if (!array_key_exists('subjects', $params)) {
            return $params;
        }

        $subjects = explode(' ', $params['subjects']);
        $subjects = array_filter($subjects, function ($i) use ($subject) {
            return $i != $subject;
        });
        $params['subjects'] = implode(' ', $subjects);

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
