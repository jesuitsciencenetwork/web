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

    public function renderDescription($description, $exclude = array())
    {
        return preg_replace_callback('/\{(S|P|R|M):(.+?)\|(.+?)\}/', function($matches) use ($exclude) {
            if (in_array($matches[2], $exclude)) {
                return $matches[3];
            }
            switch ($matches[1]) {
                case "S":
                    $url = $this->router->generate('subject', array('slug' => $matches[2]));
                    break;
                case "P":
                    $url = $this->router->generate('detail', array('id' => $matches[2]));
                    break;
                case "M":
                    list($lat,$lng) = explode(',', $matches[2]);
                    return '<a class="geo" href="#" data-lat="'.$lat.'" data-lng="'.$lng.'">'. $matches[3] . '</a>';
//                case "R":
//                    $url = $this->router->generate('subject', array('slug' => $matches[2]));
//                    break;
                default:
                    // should not happen - return unmodified
                    return $matches[0];
            }
            return '<a href="'. $url .'">' . $matches[3] . '</a>';
        }, $description);
    }
}