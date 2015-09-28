<?php

namespace AppBundle\Twig;

use JoliTypo\Fixer;

class AppExtension extends \Twig_Extension
{
    /** @var Fixer */
    private $fixer;

    public function __construct()
    {
        $this->fixer = new Fixer(array('Ellipsis', 'Dash', 'EnglishQuotes', 'CurlyQuote'));
    }
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('smart_quotes', array($this, 'smartQuotes'), array(
                'is_safe' => array('html')
            ))
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

}
