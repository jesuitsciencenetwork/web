<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class StaticController extends Controller
{
    /**
     * @Route("/about/", name="about")
     */
    public function aboutAction()
    {
        return $this->render('static/about.html.twig', []);
    }

    /**
     * @Route("/workshop/2015/", name="workshop")
     */
    public function workshopAction()
    {
        return $this->render('static/workshop.html.twig', []);
    }
}
