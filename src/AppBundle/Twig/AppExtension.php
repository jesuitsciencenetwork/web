<?php

namespace AppBundle\Twig;

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

    private static $forceUppercase = array(
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
    );

    public function __construct(RenderingHelper $helper, StatsProvider $provider)
    {
        $this->helper = $helper;
        $this->fixer = new Fixer(array('Ellipsis', 'Dash', 'EnglishQuotes', 'CurlyQuote'));
        $this->statsProvider = $provider;
    }
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('smart_quotes', array($this, 'smartQuotes'), array(
                'is_safe' => array('html')
            )),
            new \Twig_SimpleFilter('replace_links', array($this->helper, 'renderDescription'), array(
                'is_safe' => array('html')
            )),
            new \Twig_SimpleFilter('format_country', array('AppBundle\Helper', 'formatCountry')),
            new \Twig_SimpleFilter('slugify', array('Helper', 'slugify')),
            new \Twig_SimpleFilter('format_continent', array('AppBundle\Helper', 'formatContinent')),
        );
    }

    public function getTests()
    {
        return array(
            new \Twig_SimpleTest('lowercaseable', array($this, 'lowercaseable')),
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('stats', array($this->statsProvider, 'get'))
        );
    }
    public function smartQuotes($value)
    {
        return $this->fixer->fix($value);
    }
    public function getName()
    {
        return 'app';
    }


    public function lowercaseable($value)
    {
        if (in_array($value, self::$forceUppercase)) {
            return false;
        }

        return true;
    }
}
