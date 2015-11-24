<?php

namespace AppBundle\Controller;

use AppBundle\DTO\Location;
use AppBundle\DTO\Radius;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends Controller
{
    /**
     * @Route("/search/", name="search")
     */
    public function searchAction(Request $request)
    {
        $q = $request->query;

        $searchService = $this->get('jsn.search');

        $query = new \AppBundle\Query();

        if ($q->has('lat') && $q->has('lng') && $q->has('radius')) {
            // where query
            $loc = new Location($q->get('lat'), $q->get('lng'), 'XX');
            $loc->setDescription($q->get('place'));
            $radius = new Radius($loc, $q->get('radius'));
            $query->setRadius($radius);
        } elseif ($q->has('continent')) {
            $query->setContinent($q->get('continent'));
        } elseif ($q->has('country')) {
            $query->setCountry($q->get('country'));
        } elseif ($q->has('subjects')) {
            // what query
            $ids = explode(',', $q->get('subjects'));
            $ids = array_map(function($e) {return (int)$e;}, $ids);
            $ids = array_unique($ids);

            $subjResult = $this->getDoctrine()->getRepository('AppBundle:Subject')
                ->createQueryBuilder('s')
                ->select('s.id, s.title')
                ->orderBy('s.title', 'ASC')
                ->where('s.id IN(:ids)')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
            ;

            $subjects = array();
            foreach ($subjResult as $subject) {
                $subjects[$subject['id']] = $subject['title'];
            }
            $query->setSubjects($subjects);
        } elseif ($q->has('from') && $q->has('to')) {
            // when query
            $query->setFrom($q->get('from'));
            $query->setTo($q->get('to'));
        } elseif ($q->has('occupation')) {
            $query->setOccupation($q->get('occupation'));
        } else {
            if ($q->count() > 0) {
                $this->get('braincrafted_bootstrap.flash')->alert('Your search query could not be understood.');
            }

            return $this->render('default/search.html.twig', array(
                'subjectGroupTree' => $searchService->getSubjectGroupTree()
            ));
        }

        return $searchService->render($query, $request->get('page', 1));

    }
}
