<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Aspect
 * @ORM\Entity
 */
class Aspect
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Person
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person", inversedBy="aspects")
     */
    private $person;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dateExact;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dateFrom;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dateTo;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $placeName;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $country;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $continent;

    /**
     * @var float
     * @ORM\Column(type="decimal", precision=11, scale=8, nullable=true)
     */
    private $latitude;

    /**
     * @var float
     * @ORM\Column(type="decimal", precision=11, scale=8, nullable=true)
     */
    private $longitude;

    private $source;

    /**
     * @var Subject[]|Collection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Subject", inversedBy="associatedAspects")
     * @ORM\JoinTable(name="aspect_subject")
     */
    private $subjects;

    /**
     * @var string
     * @ORM\Column(type="string", length=10000, nullable=true)
     */
    private $description;

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return Aspect
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Aspect
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set dateFrom
     *
     * @param integer $dateFrom
     *
     * @return Aspect
     */
    public function setDateFrom($dateFrom)
    {
        $this->dateFrom = $dateFrom;

        return $this;
    }

    /**
     * Get dateFrom
     *
     * @return integer
     */
    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    /**
     * Set dateTo
     *
     * @param integer $dateTo
     *
     * @return Aspect
     */
    public function setDateTo($dateTo)
    {
        $this->dateTo = $dateTo;

        return $this;
    }

    /**
     * Get dateTo
     *
     * @return integer
     */
    public function getDateTo()
    {
        return $this->dateTo;
    }

    /**
     * Set placeName
     *
     * @param string $placeName
     *
     * @return Aspect
     */
    public function setPlaceName($placeName)
    {
        $this->placeName = $placeName;

        return $this;
    }

    /**
     * Get placeName
     *
     * @return string
     */
    public function getPlaceName()
    {
        return $this->placeName;
    }

    /**
     * Set latitude
     *
     * @param string $latitude
     *
     * @return Aspect
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param string $longitude
     *
     * @return Aspect
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Aspect
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set person
     *
     * @param \AppBundle\Entity\Person $person
     *
     * @return Aspect
     */
    public function setPerson(\AppBundle\Entity\Person $person = null)
    {
        $this->person = $person;

        return $this;
    }

    /**
     * Get person
     *
     * @return \AppBundle\Entity\Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * Set dateExact
     *
     * @param integer $dateExact
     *
     * @return Aspect
     */
    public function setDateExact($dateExact)
    {
        $this->dateExact = $dateExact;

        return $this;
    }

    /**
     * Get dateExact
     *
     * @return integer
     */
    public function getDateExact()
    {
        return $this->dateExact;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->subjects = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add subject
     *
     * @param \AppBundle\Entity\Subject $subject
     *
     * @return Aspect
     */
    public function addSubject(\AppBundle\Entity\Subject $subject)
    {
        $this->subjects[] = $subject;

        return $this;
    }

    /**
     * Remove subject
     *
     * @param \AppBundle\Entity\Subject $subject
     */
    public function removeSubject(\AppBundle\Entity\Subject $subject)
    {
        $this->subjects->removeElement($subject);
    }

    /**
     * Get subjects
     *
     * @return Subject[]|\Doctrine\Common\Collections\Collection
     */
    public function getSubjects()
    {
        return $this->subjects;
    }
}
