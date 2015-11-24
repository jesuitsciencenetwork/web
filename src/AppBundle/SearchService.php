<?php

namespace AppBundle;

use AppBundle\DTO\Radius;
use AppBundle\Entity\Subject;
use AppBundle\Entity\SubjectGroup;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use AppBundle\Query as QueryDTO;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class SearchService
{
    /** @var EngineInterface */
    private $templating;

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var PaginatorInterface $paginator */
    private $paginator;

    /**
     * SearchService constructor
     *
     * @param EngineInterface $templating
     * @param EntityManagerInterface $em
     */
    public function __construct(EngineInterface $templating, EntityManagerInterface $em, $paginator)
    {
        $this->templating = $templating;
        $this->em = $em;
        $this->paginator = $paginator;
    }

    public function render(QueryDTO $query, $page = 1)
    {
        $qb = $this->em
            ->createQueryBuilder()
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
            $ids = $this->placesNear($query->getRadius());
            $qb->andWhere('pl.id IN(:radius)');
            $qb->setParameter('radius', $ids);
        }

        if ($query->getFrom()) {
            $qb->andWhere('COALESCE(a.dateExact, a.dateFrom) >= :from');
            $qb->setParameter('from', $query->getFrom());
        }

        if ($query->getTo()) {
            $qb->andWhere('COALESCE(a.dateExact, a.dateTo) <= :to');
            $qb->setParameter('to', $query->getTo());
        }

        if ($query->getSubjects()) {
            $sids = array_keys($query->getSubjects());
            $pqb = $this->em->getRepository('AppBundle:Person')->createQueryBuilder('p')
                ->select('p.id');
            foreach ($sids as $id) {
                $pqb->innerJoin('p.subjects', 'si'.$id, Query\Expr\Join::WITH, "si$id.id=$id");
            }
            $pids = $pqb->getQuery()->getResult(Query::HYDRATE_ARRAY);
            $pids = array_map(function ($e) { return $e['id']; }, $pids);
            $qb->andWhere('p.id IN(:pids)');
            $qb->innerJoin('a.subjects', 'si', Query\Expr\Join::WITH, "si.id IN (:sids)");
            $qb->setParameter('sids', $sids);
            $qb->setParameter('pids', $pids);
        }

        if ($query->getOccupation()) {
            $qb->andWhere('a.occupation = :occupation');
            $qb->setParameter('occupation', $query->getOccupation());
        }

        $filter = $this->getFilters(clone $qb);

        $pagination = $this->paginator->paginate($qb->getQuery(), $page, 20);

        return $this->templating->renderResponse('search/results.html.twig', array(
            'pagination' => $pagination,
            'query' => $query,
            'filter' => $filter
        ));
    }

    private function getFilters(QueryBuilder $qb)
    {
        $filters = array();

        $countries = $qb->select('distinct pl.country')->getQuery()->getResult(Query::HYDRATE_ARRAY);
        $filters['countries'] = array_map(function($e) {
            return $e['country'];
        }, $countries);

        $continents = $qb->select('distinct pl.continent')->getQuery()->getResult(Query::HYDRATE_ARRAY);
        $filters['continents'] = array_map(function($e) {
            return $e['continent'];
        }, $continents);

        $filters['subjects'] = $qb
            ->select('distinct s.id, s.title')->getQuery()->getResult(Query::HYDRATE_ARRAY);

        $sourceIds = $qb->select('distinct src.id')->getQuery()->getResult(Query::HYDRATE_ARRAY);
        $sourceIds = array_map(function($e) {
            return $e['id'];
        }, $sourceIds);
        $filters['sources'] = $this->em->createQueryBuilder()->select('s')->from('AppBundle:Source', 's')->where('s.id IN(:ids)')->orderBy('s.id', 'asc')->setParameter('ids', $sourceIds)->getQuery()->getResult();


        return $filters;
    }

    private function placesNear(Radius $radius)
    {
        $rows = $this->em->getConnection()->executeQuery(
            'SELECT id, (6371 * acos(cos(radians(:lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians(:lng)) + sin(radians(:lat)) * sin(radians(latitude)))) AS distance FROM place HAVING distance <= :radius ORDER BY distance ASC',
        array(
            'lat' => $radius->getCenter()->getLatitude(),
            'lng' => $radius->getCenter()->getLongitude(),
            'radius' => $radius->getRadius()
        ));

        $ids = array();

        foreach ($rows as $row) {
            $ids[] = $row['id'];
        }

        return $ids;
    }

    public function getSubjectGroupTree()
    {
        $q = $this
            ->em
            ->getRepository('AppBundle:SubjectGroup')
            ->createQueryBuilder('g')
            ->select('g, s')
            ->leftJoin('g.subjects', 's')
            ->where('g.scheme = :scheme')
            ->addOrderBy('g.title', 'ASC')
            ->addOrderBy('s.title', 'ASC')
            ->getQuery()
        ;

        $contemporary = $q->execute(array('scheme' => 'harris'));
        $modern = $q->execute(array('scheme' => 'modern'));

        $callback = function (SubjectGroup $group) {
            return array(
                'text' => $group->getTitle(),
                'selectable' => false,
                'disableCheckbox' => true,
                'nodes' => $group->getSubjects()->map(function (Subject $s) {
                    return array(
                        'text' => $s->getTitle(),
                        'id' => $s->getId()
                    );
                })->toArray(),
                'state' => array('expanded' => $group->getSubjects()->count() <= 3)
            );
        };

        return array(
            array(
                'text' =>  '<em>Contemporary grouping</em>',
                'selectable' => false,
                'disableCheckbox' => true,
                'nodes' => array_map($callback, $contemporary)
            ),
            array(
                'text' =>  '<em>Modern grouping</em>',
                'selectable' => false,
                'disableCheckbox' => true,
                'nodes' => array_map($callback, $modern)
            ),
        );
    }
}
