<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Person;
use AppBundle\Entity\Place;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class DebugController
 *
 * @Route("/debug")
 */
class DebugController extends Controller
{
    /**
     * @Route("/sources/", name="debug_sources")
     */
    public function sourcesAction()
    {
        $sources = $this->getDoctrine()->getManager()->createQuery('SELECT s FROM AppBundle:Source s WHERE s.genre <> ?0 and s.genre <> ?1 order by s.id asc')->execute(
            ['VIAF', 'GND']
        );
        return $this->render('default/sources.html.twig', [
            'sources' => $sources
        ]
        );
    }

    /**
     * @Route("/places/", name="debug_places")
     */
    public function placesAction()
    {
        /** @var Place[] $placeResult */
        $placeResult = $this->getDoctrine()->getManager()
            ->createQuery('SELECT pl, pe FROM AppBundle:Place pl LEFT JOIN pl.associatedPersons pe ORDER BY pl.placeName')
            ->execute();

        $places = [];
        foreach ($placeResult as $place) {
            $places[] = [
                'name' => $place->getPlaceName(),
                'lat' => $place->getLatitude(),
                'lng' => $place->getLongitude(),
                'persons' => "<strong>" . $place->getPlaceName() . "</strong><br>" . implode("<br/>", $place->getAssociatedPersons()->map(function (Person $p) {
                    return $p->getListName();
                })->toArray())
            ];
        }

        return $this->render('map/debug.html.twig', [
            'places' => $places
        ]
        );
    }
}
