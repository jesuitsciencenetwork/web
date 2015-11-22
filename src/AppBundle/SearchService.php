<?php

namespace AppBundle;

use Doctrine\ORM\EntityManagerInterface;
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

    public function render(Query $search, $page = 1)
    {
        $qb = $this->em
            ->createQueryBuilder()
            ->select('a, p')
            ->from('AppBundle:Aspect', 'a')
            ->innerJoin('a.person', 'p')
            ->leftJoin('a.places', 'pl')
        ;

        $search->apply($qb);

        $pagination = $this->paginator->paginate($qb->getQuery(), $page, 20);

        return $this->templating->renderResponse('search/results.html.twig', array(
            'pagination' => $pagination
        ));
    }
}
