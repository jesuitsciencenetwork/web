<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Person;
use AppBundle\Form\MapFilterForm;
use Doctrine\ORM\Query;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class MapController extends Controller
{
    /**
     * @Route("/map/", name="map")
     */
    public function indexAction(Request $request)
    {
        $form = $this->createForm(new MapFilterForm());
        return $this->render('map/index.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/debug-places/", name="debug_places")
     */
    public function debugAction(Request $request)
    {
        $aspects = $this->getDoctrine()->getManager()
            ->createQuery('SELECT a, p FROM AppBundle:Aspect a LEFT JOIN a.person p WHERE a.placeName IS NOT NULL ORDER BY a.placeName')
            ->execute();

        $places = array();
        foreach ($aspects as $aspect) {
            $placeName = $aspect->getPlaceName();
            if (!array_key_exists($placeName, $places)) {
                $places[$placeName] = array(
                    'name' => $placeName,
                    'lat' => $aspect->getLatitude(),
                    'lng' => $aspect->getLongitude(),
                    'persons' => array()
                );
            }
            $person = $aspect->getPerson();
            $places[$placeName]['persons'][$person->getId()] = $person;
        }
        foreach ($places as &$place) {
            $place['persons'] = "<strong>". $place['name'] ."</strong><br>".implode("<br/>", array_map(function(Person $p) {
                return $p->getListName();
            }, $place['persons']));
        }

        return $this->render('map/debug.html.twig', array(
            'places' => array_values($places)
        ));
    }


}
