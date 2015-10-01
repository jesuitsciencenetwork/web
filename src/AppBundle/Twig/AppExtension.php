<?php

namespace AppBundle\Twig;

use AppBundle\Twig\Helper\RenderingHelper;
use JoliTypo\Fixer;

class AppExtension extends \Twig_Extension
{
    /** @var Fixer */
    private $fixer;

    /** @var RenderingHelper */
    private $helper;

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
