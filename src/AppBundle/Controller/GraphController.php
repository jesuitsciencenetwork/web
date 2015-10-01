<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class GraphController extends Controller
{
    /**
     * @Route("/graph/", name="graph")
     */
    public function indexAction()
    {
        return $this->render('graph/index.html.twig');
    }
}
