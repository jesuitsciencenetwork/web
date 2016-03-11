<?php

namespace AppBundle\Controller;

use AppBundle\Exception\InvalidQueryException;
use AppBundle\Exception\QueryException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SearchController
 * @package AppBundle\Controller
 */
class SearchController extends Controller
{
    /**
     * @Route("/search/", name="search")
     */
    public function searchAction(Request $request)
    {
        $searchService = $this->get('jsn.search');

        try {
            $query = $searchService->getQueryFromRequest($request);
        } catch (QueryException $e) {
            $message = ($e instanceof InvalidQueryException)
                     ? 'Your search query could not be understood.'
                     : null;

            return $this->render('default/search.html.twig', [
                'message' => $message
            ]);
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
        ]);
    }
}
