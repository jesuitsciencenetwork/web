<?php

namespace AppBundle;

use Doctrine\ORM\QueryBuilder;
use AppBundle\Query as QueryDTO;
use Doctrine\ORM\Query as Query;

class Search
{
    /** @var SearchService */
    private $searchService;

    /** @var Query */
    private $query;

    /** @var QueryBuilder */
    private $queryBuilder;


    /**
     * Search constructor.
     * @param SearchService $searchService
     */
    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    public function execute(QueryDTO $query)
    {
        $this->query = $query;

        $this->queryBuilder = $this->createQueryBuilder($query);
    }

    private function createQueryBuilder(QueryDTO $query)
    {
        $qb = $this->searchService->createQueryBuilder()
            ->select('a, p')
            ->from('AppBundle:Aspect', 'a')
            ->innerJoin('a.person', 'p')
            ->leftJoin('a.places', 'pl')
            ->leftJoin('a.subjects', 's')
            ->leftJoin('a.source', 'src')
        ;

        if ($query->getCountry()) {
            $qb->andWhere('pl.country = :country');
            $qb->setParameter('country', $query->getCountry());
        }

        if ($query->getContinent()) {
            $qb->andWhere('pl.continent = :continent');
            $qb->setParameter('continent', $query->getContinent());
        }

        if ($query->getRadius()) {
            $ids = $this->searchService->placesNear($query->getRadius());
            $qb->andWhere('pl.id IN(:radius)');
            $qb->setParameter('radius', $ids);
        }

        if ($bounds = $query->getBounds()) {
            if ($bounds->getSouthWest()->getLongitude() > $bounds->getNorthEast()->getLongitude()) {
                // 180 degree wrap-around
                $qb->andWhere('pl.longitude NOT BETWEEN :swlng AND :nelng');
            } else {
                $qb->andWhere('pl.longitude BETWEEN :swlng AND :nelng');
            }
            $qb->andWhere('pl.latitude BETWEEN :swlat AND :nelat');

            $qb->setParameter('swlat', $bounds->getSouthWest()->getLatitude());
            $qb->setParameter('swlng', $bounds->getSouthWest()->getLongitude());
            $qb->setParameter('nelat', $bounds->getNorthEast()->getLatitude());
            $qb->setParameter('nelng', $bounds->getNorthEast()->getLongitude());
        }

        if ($query->getFrom()) {
            $qb->andWhere('COALESCE(a.dateExact, a.dateFrom, a.dateTo) >= :from');
            $qb->setParameter('from', $query->getFrom());
        }

        if ($query->getTo()) {
            $qb->andWhere('COALESCE(a.dateExact, a.dateTo, a.dateFrom) <= :to');
            $qb->setParameter('to', $query->getTo());
        }

        if ($query->getSubjects()) {
            $sids = array_keys($query->getSubjects());
            $pids = $this->searchService->getPersonsForSubjects($sids);
            $qb->andWhere('p.id IN(:pids)');
            $qb->innerJoin('a.subjects', 'si', Query\Expr\Join::WITH, "si.id IN (:sids)");
            $qb->setParameter('sids', $sids);
            $qb->setParameter('pids', $pids);
        }

        if ($query->getOccupation()) {
            $qb->andWhere('a.occupation = :occupation');
            $qb->setParameter('occupation', $query->getOccupation());
        }

        return $qb;
    }
}
