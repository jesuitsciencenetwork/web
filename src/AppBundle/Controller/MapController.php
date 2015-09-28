<?php

namespace AppBundle\Controller;

use AppBundle\Form\MapFilterForm;
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
}
