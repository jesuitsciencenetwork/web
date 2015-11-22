<?php

namespace AppBundle;


use Doctrine\ORM\QueryBuilder;

class Query
{
    private $continent;
    private $country;

    private $radius;

    /**
     * @return mixed
     */
    public function getContinent()
    {
        return $this->continent;
    }

    /**
     * @param mixed $continent
     */
    public function setContinent($continent)
    {
        $this->continent = $continent;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    public function apply(QueryBuilder $qb)
    {
        if ($this->country) {
            $qb->andWhere('pl.country = :country');
            $qb->setParameter('country', $this->country);
        }

        if ($this->continent) {
            $qb->andWhere('pl.continent = :continent');
            $qb->setParameter('continent', $this->continent);
        }
    }
}
