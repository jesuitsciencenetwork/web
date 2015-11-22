<?php

namespace AppBundle;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

class StatsProvider
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var array */
    private static $stats = null;

    /**
     * StatsProvider constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function get()
    {
        if (null === self::$stats) {
            $stats = $this->em->createQuery(
                'SELECT COUNT(p.id) as nb, MAX(p.lastMod) as lm FROM AppBundle:Person p'
            )->getResult(Query::HYDRATE_ARRAY);

            self::$stats = $stats[0];
        }

        return self::$stats;
    }
}
