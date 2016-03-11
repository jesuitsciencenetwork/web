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
    private $occupation;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $affiliation;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $occupationSlug;

    /**
     * @var Place[]|Collection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Place", inversedBy="associatedAspects")
     */
    private $places;

    /**
     * @var Source
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Source", inversedBy="associatedAspects")
     */
    private $source;

    /**
     * @var Subject[]|Collection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Subject", inversedBy="associatedAspects")
     * @ORM\JoinTable(name="aspect_subject")
     */
    private $subjects;

    /**
     * @var Relation[]|Collection
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Relation", mappedBy="aspect")
     */
    private $relations;

    /**
     * @var string
     * @ORM\Column(type="string", length=10000, nullable=true)
     */
    private $comment;

    /**
     * @var string
     * @ORM\Column(type="string", length=10000, nullable=true)
     */
    private $description;

    /**
     * @var string
     * @ORM\Column(type="string", length=100000, nullable=true)
     */
    private $rawXml;

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

    /**
     * Set occupation
     *
     * @param string $occupation
     *
     * @return Aspect
     */
    public function setOccupation($occupation)
    {
        $this->occupation = $occupation;

        return $this;
    }

    /**
     * Get occupation
     *
     * @return string
     */
    public function getOccupation()
    {
        return $this->occupation;
    }

    /**
     * Set rawXml
     *
     * @param string $rawXml
     *
     * @return Aspect
     */
    public function setRawXml($rawXml)
    {
        $this->rawXml = $rawXml;

        return $this;
    }

    /**
     * Get rawXml
     *
     * @return string
     */
    public function getRawXml()
    {
        return $this->rawXml;
    }

    /**
     * Add place
     *
     * @param \AppBundle\Entity\Place $place
     *
     * @return Aspect
     */
    public function addPlace(\AppBundle\Entity\Place $place)
    {
        $this->places[] = $place;

        return $this;
    }

    /**
     * Remove place
     *
     * @param \AppBundle\Entity\Place $place
     */
    public function removePlace(\AppBundle\Entity\Place $place)
    {
        $this->places->removeElement($place);
    }

    /**
     * Get places
     *
     * @return Place[]|\Doctrine\Common\Collections\Collection
     */
    public function getPlaces()
    {
        return $this->places;
    }

    /**
     * Set source
     *
     * @param \AppBundle\Entity\Source $source
     *
     * @return Aspect
     */
    public function setSource(\AppBundle\Entity\Source $source = null)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return \AppBundle\Entity\Source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set comment
     *
     * @param string $comment
     *
     * @return Aspect
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set occupationSlug
     *
     * @param string $occupationSlug
     *
     * @return Aspect
     */
    public function setOccupationSlug($occupationSlug)
    {
        $this->occupationSlug = $occupationSlug;

        return $this;
    }

    /**
     * Get occupationSlug
     *
     * @return string
     */
    public function getOccupationSlug()
    {
        return $this->occupationSlug;
    }

    /**
     * Add relation
     *
     * @param \AppBundle\Entity\Relation $relation
     *
     * @return Aspect
     */
    public function addRelation(\AppBundle\Entity\Relation $relation)
    {
        $this->relations[] = $relation;

        return $this;
    }

    /**
     * Remove relation
     *
     * @param \AppBundle\Entity\Relation $relation
     */
    public function removeRelation(\AppBundle\Entity\Relation $relation)
    {
        $this->relations->removeElement($relation);
    }

    /**
     * Get relations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRelations()
    {
        return $this->relations;
    }

    public function getMarkerLabel()
    {
        $type = $this->getType();
        if (in_array($type, ['beginningOfLife', 'entryInTheOrder', 'resignationFromTheOrder', 'expulsionFromTheOrder', 'endOfLife']
        )) {
            return 'B';
        }
        if ('miscellaneous' == $type && $this->relations->count()) {
            return $this->relations[0]->getSource()->getId() == $this->person->getId() ? 'O' : 'I';
        }

        return strtoupper(substr($type, 0, 1));
    }

    public function isBiographical()
    {
        static $biographical = [
            "entryInTheOrder" => 1,
            "beginningOfLife" => 1,
            "endOfLife" => 1,
            "resignationFromTheOrder" => 1,
            "expulsionFromTheOrder" => 1
        ];

        return array_key_exists($this->type, $biographical);
    }

    /**
     * @return string
     */
    public function getAffiliation()
    {
        return $this->affiliation;
    }

    /**
     * @param string $affiliation
     */
    public function setAffiliation($affiliation)
    {
        $this->affiliation = $affiliation;
    }
}
