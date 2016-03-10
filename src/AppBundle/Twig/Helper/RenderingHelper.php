<?php

namespace AppBundle\Twig\Helper;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

class RenderingHelper
{
    /** @var Router */
    private $router;

    /**
     * RenderingHelper constructor.
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function renderDescription($description, $exclude = [], $doLinks = true)
    {
        return preg_replace_callback('/\{(S|P|R|M|O):(.+?)\|(.+?)\}/', function ($matches) use ($exclude, $doLinks) {
            if (in_array($matches[2], $exclude) || !$doLinks) {
                return $matches[3];
            }
            switch ($matches[1]) {
                case "S":
                    $url = $this->router->generate('search', ['subjects' => $matches[2]]);
                    break;
                case "P":
                    $url = $this->router->generate('detail', ['id' => $matches[2]]);
                    break;
                case "O":
                    $url = $this->router->generate('search', ['occupation' => $matches[2]]);
                    break;
                case "M":
                    list($lat,$lng) = explode(',', $matches[2]);
                    $url = $this->router->generate('search', ['lat' => $lat, 'lng'=>$lng, 'radius'=>0, 'placeName'=>$matches[3]]);
                    return '<a href="'.$url.'">' . $matches[3] . '</a><a class="geo" href="#" data-lat="'.$lat.'" data-lng="'.$lng.'"><i class="fa fa-map-marker"></i></a>';
                default:
                    // should not happen - return unmodified
                    return $matches[0];
            }
            return '<a href="'. $url .'">' . $matches[3] . '</a>';
        }, $description);
    }
}
