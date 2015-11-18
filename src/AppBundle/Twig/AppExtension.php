<?php

namespace AppBundle\Twig;

use AppBundle\Twig\Helper\RenderingHelper;
use JoliTypo\Fixer;
use Symfony\Component\Intl\Intl;

class AppExtension extends \Twig_Extension
{
    /** @var Fixer */
    private $fixer;

    /** @var RenderingHelper */
    private $helper;

    private static $continents = array(
        'AF' => 'Africa',
        'AN' => 'Antarctica',
        'AS' => 'Asia',
        'EU' => 'Europe',
        'NA' => 'North America',
        'OC' => 'Oceania',
        'SA' => 'South America'
    );

    public function __construct(RenderingHelper $helper)
    {
        $this->helper = $helper;
        $this->fixer = new Fixer(array('Ellipsis', 'Dash', 'EnglishQuotes', 'CurlyQuote'));
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
            new \Twig_SimpleFilter('format_country', array($this, 'formatCountry')),
            new \Twig_SimpleFilter('slugify', array('Helper', 'slugify')),
            new \Twig_SimpleFilter('format_continent', array($this, 'formatContinent')),
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

    public function formatCountry($value)
    {
        return Intl::getRegionBundle()->getCountryName($value, 'en_US');
    }

    public function formatContinent($value)
    {
        if (!array_key_exists($value, self::$continents)) {
            return '';
        }
        return self::$continents[$value];
    }

}
