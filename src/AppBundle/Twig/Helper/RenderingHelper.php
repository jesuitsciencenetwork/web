<?php

namespace AppBundle\Twig\Helper;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

class RenderingHelper
{
    /** @var Router */
    private $router;

    private static $continents = array(
        'AF' => 'Africa',
        'AN' => 'Antarctica',
        'AS' => 'Asia',
        'EU' => 'Europe',
        'NA' => 'North America',
        'OC' => 'Oceania',
        'SA' => 'South America'
    );

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

    /**
     * RenderingHelper constructor.
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function renderDescription($description, $exclude = array(), $doLinks = true)
    {
        return preg_replace_callback('/\{(S|P|R|M|O):(.+?)\|(.+?)\}/', function($matches) use ($exclude, $doLinks) {
            if (in_array($matches[2], $exclude) || !$doLinks) {
                return $matches[3];
            }
            switch ($matches[1]) {
                case "S":
                    $url = $this->router->generate('search', array('subjects' => $matches[2]));
                    break;
                case "P":
                    $url = $this->router->generate('detail', array('id' => $matches[2]));
                    break;
                case "O":
                    $url = $this->router->generate('search', array('occupation' => $matches[2]));
                    break;
                case "M":
                    list($lat,$lng) = explode(',', $matches[2]);
                    $url = $this->router->generate('search', array('lat' => $lat, 'lng'=>$lng, 'radius'=>0, 'place'=>$matches[3]));
                    return '<a href="'.$url.'">' . $matches[3] . '</a><a class="geo" href="#" data-lat="'.$lat.'" data-lng="'.$lng.'"><i class="fa fa-map-marker"></i></a>';
                default:
                    // should not happen - return unmodified
                    return $matches[0];
            }
            return '<a href="'. $url .'">' . $matches[3] . '</a>';
        }, $description);
    }
}
