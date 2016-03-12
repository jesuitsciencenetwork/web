<?php

namespace AppBundle;


use AppBundle\DTO\Bounds;
use AppBundle\DTO\Radius;
use AppBundle\Entity\Place;

class Query
{
    const TYPE_BIOGRAPHICAL = 1;
    const TYPE_EDUCATION = 2;
    const TYPE_CAREER = 4;
    const TYPE_OTHER = 8;

    private $types = 0;

    private $continent;
    private $country;

    /** @var Radius */
    private $radius;

    /** @var Bounds */
    private $bounds;


    private $from;
    private $to;

    private $subjects;

    /** @var Place */
    private $place;

    private $occupation;

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

    /**
     * @return Radius
     */
    public function getRadius()
    {
        return $this->radius;
    }

    /**
     * @param Radius $radius
     */
    public function setRadius($radius)
    {
        $this->radius = $radius;
    }

    /**
     * @return mixed
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param mixed $from
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }

    /**
     * @return mixed
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param mixed $to
     */
    public function setTo($to)
    {
        $this->to = $to;
    }

    /**
     * @return mixed
     */
    public function getSubjects()
    {
        return $this->subjects;
    }

    /**
     * @param mixed $subjects
     */
    public function setSubjects($subjects)
    {
        $this->subjects = $subjects;
    }

    /**
     * @return mixed
     */
    public function getOccupation()
    {
        return $this->occupation;
    }

    /**
     * @param mixed $occupation
     */
    public function setOccupation($occupation)
    {
        $this->occupation = $occupation;
    }

    /**
     * @return Bounds
     */
    public function getBounds()
    {
        return $this->bounds;
    }

    /**
     * @param Bounds $bounds
     */
    public function setBounds(Bounds $bounds)
    {
        $this->bounds = $bounds;
    }

    /**
     * @return Place
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * @param Place $place
     */
    public function setPlace($place)
    {
        $this->place = $place;
    }

    public function getBiographical()
    {
        return (bool)($this->types & self::TYPE_BIOGRAPHICAL);
    }

    public function getCareer()
    {
        return (bool)($this->types & self::TYPE_CAREER);
    }

    public function getEducation()
    {
        return (bool)($this->types & self::TYPE_EDUCATION);
    }

    public function getOther()
    {
        return (bool)($this->types & self::TYPE_OTHER);
    }

    public function setTypes($types)
    {
        $this->types = $types;
    }

    public function getTypeValue()
    {
        return $this->types;
    }

    public function hasTypeRestriction()
    {
        return $this->types < self::types();
    }

    public static function types()
    {
        return self::TYPE_BIOGRAPHICAL | self::TYPE_CAREER | self::TYPE_EDUCATION | self::TYPE_OTHER;
    }
}
