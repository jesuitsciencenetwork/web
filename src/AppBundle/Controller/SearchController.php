<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends Controller
{
    /**
     * @Route("/search2/:what/:when/:where/", name="search2")
     */
    public function search2Action(Request $request)
    {
        //parse what = fulltext search
        //parse when = ^(\d+)?-(\d+)?$ ohne "-"
        //parse where = entweder placename oder latlng+radius oder latlng bnds

    }

        /**
     * @Route("/search/", name="search")
     */
    public function searchAction(Request $request)
    {
        $searchService = $this->get('jsn.search');

        try {
            $query = $searchService->getQueryFromRequest($request);
        } catch (\Exception $e) {
            $this->addFlash('alert', 'Your search query could not be understood.');
            return $this->render('default/search.html.twig', [
                'subjectGroupTree' => $searchService->getSubjectGroupTree()
            ]
            );
        }

        $search = $searchService->create();
        $search->execute($query);
        $qb = $search->getQueryBuilder();
        $filter = $searchService->getFilters(clone $qb);

        $pagination = $this->get('knp_paginator')->paginate(
            $qb->getQuery(),
            $request->get('page', 1),
            20
        );

        return $this->render('search/results.html.twig', [
            'pagination' => $pagination,
            'query' => $query,
            'filter' => $filter
        ]
        );
    }
}
