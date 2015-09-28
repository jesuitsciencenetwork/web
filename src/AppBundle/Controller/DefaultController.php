<?php

namespace AppBundle\Controller;

use AppBundle\Form\AdvancedSearchForm;
use AppBundle\Helper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return $this->render('default/index.html.twig', array());
    }

    /**
     * @Route("/p/{id}/", name="detail")
     */
    public function detailAction($id, Request $request)
    {
        $person = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Person')
            ->find($id);

        if (null === $person) {
            throw new NotFoundHttpException('Person not found');
        }

        $aspects = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Aspect')
            ->createQueryBuilder('a')
            ->select('a, COALESCE(a.dateFrom, a.dateTo) as orderDate')
            ->orderBy('orderDate', 'ASC')
            ->where('a.person = :person')
            ->setParameter('person', $person->getId())
            ->getQuery()
            ->execute()
        ;

        return $this->render('default/detail.html.twig', array(
            'person' => $person,
            'aspects' => $aspects
        ));
    }

    /**
     * @Route("/list/", name="list")
     */
    public function listAction()
    {
        $persons = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Person')
            ->findBy(array(), array('lastName' => 'ASC', 'firstName' => 'ASC'))
        ;

        $letters = array();

        foreach ($persons as $person) {
            $letter = strtoupper(Helper::removeAccents(mb_substr($person->getLastName(), 0, 1)));
            if (!array_key_exists($letter, $letters)) {
                $letters[$letter] = array();
            }

            $letters[$letter][] = $person;
        }

        return $this->render('default/list.html.twig', array(
            'letters' => $letters
        ));
    }

    /**
     * @Route("/viaf/{id}/", name="viaf")
     */
    public function viafAction($id)
    {
        $person = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Person')
            ->findOneBy(array('viafId' => $id));

        if (!$person) {
            $this
                ->get('braincrafted_bootstrap.flash')
                ->alert(sprintf(
                    'The VIAF ID "%s" could not be found. Please try searching our database instead.',
                    $id
                ))
            ;
            return $this->redirect($this->generateUrl('search'));
        }

        return $this->redirect(
            $this->generateUrl('detail', array('id' => $person->getId()))
        );
    }

    /**
     * @Route("/imprint/", name="imprint")
     */
    public function imprintAction()
    {
        return $this->render('default/imprint.html.twig');
    }

    /**
     * @Route("/workshop/2015/", name="workshop")
     */
    public function workshopAction()
    {
        return $this->render('default/workshop.html.twig', array());
    }

    /**
     * @Route("/search/", name="search")
     */
    public function searchAction(Request $request)
    {
        $form = $this->createForm(new AdvancedSearchForm());

        $form->handleRequest($request);

        if ($form->isValid()) {
            return $this->render('default/results.html.twig', array(
                'form' => $form->createView()
            ));
        }

        return $this->render('default/search.html.twig', array(
            'form' => $form->createView()
        ));
    }
}
