<?php

namespace AppBundle\Entity;

use AppBundle\Helper;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Person
 * @ORM\Entity
 */
class Person
{
    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id()
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $displayName;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $listName;

    /**
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $isJesuit = true;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    private $viafId;

    /**
     * @var integer
     * @ORM\Column(name="lastmod", type="datetime", nullable=true)
     */
    private $lastMod;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dateOfBirth;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dateOfDeath;

    /**
     * @var AlternateName[]|Collection
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\AlternateName", mappedBy="person")
     */
    private $alternateNames;

    /**
     * @var Subject[]|Collection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Subject", inversedBy="associatedPersons")
     * @ORM\JoinTable(name="person_subject")
     */
    private $subjects;

    /**
     * @var Place[]|Collection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Place", inversedBy="associatedPersons")
     */
    private $places;

    /**
     * @var Source[]|Collection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Source", inversedBy="associatedPersons")
     */
    private $sources;

    /**
     * @var Aspect[]|Collection
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Aspect", mappedBy="person")
     */
    private $aspects;

    /**
     * @var Person[]|Collection
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Relation", mappedBy="target")
     */
    private $relationsIncoming;

    /**
     * @var Person[]|Collection
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Relation", mappedBy="source")
     */
    private $relationsOutgoing;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * @param int $dateOfBirth
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;
    }

    /**
     * @return int
     */
    public function getDateOfDeath()
    {
        return $this->dateOfDeath;
    }

    /**
     * @param int $dateOfDeath
     */
    public function setDateOfDeath($dateOfDeath)
    {
        $this->dateOfDeath = $dateOfDeath;
    }


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->alternateNames = new \Doctrine\Common\Collections\ArrayCollection();
        $this->subjects = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set viafId
     *
     * @param integer $viafId
     *
     * @return Person
     */
    public function setViafId($viafId)
    {
        $this->viafId = $viafId;

        return $this;
    }

    /**
     * Get viafId
     *
     * @return integer
     */
    public function getViafId()
    {
        return $this->viafId;
    }

    /**
     * Add alternateName
     *
     * @param \AppBundle\Entity\AlternateName $alternateName
     *
     * @return Person
     */
    public function addAlternateName(\AppBundle\Entity\AlternateName $alternateName)
    {
        $this->alternateNames[] = $alternateName;

        return $this;
    }

    /**
     * Remove alternateName
     *
     * @param \AppBundle\Entity\AlternateName $alternateName
     */
    public function removeAlternateName(\AppBundle\Entity\AlternateName $alternateName)
    {
        $this->alternateNames->removeElement($alternateName);
    }

    /**
     * Get alternateNames
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAlternateNames()
    {
        return $this->alternateNames;
    }

    /**
     * Add subject
     *
     * @param \AppBundle\Entity\Subject $subject
     *
     * @return Person
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
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSubjects()
    {
        return $this->subjects;
    }

    public function getPdrId()
    {
        return Helper::num2pdr($this->id);
    }

    /**
     * Add aspect
     *
     * @param \AppBundle\Entity\Aspect $aspect
     *
     * @return Person
     */
    public function addAspect(\AppBundle\Entity\Aspect $aspect)
    {
        $this->aspects[] = $aspect;

        return $this;
    }

    /**
     * Remove aspect
     *
     * @param \AppBundle\Entity\Aspect $aspect
     */
    public function removeAspect(\AppBundle\Entity\Aspect $aspect)
    {
        $this->aspects->removeElement($aspect);
    }

    /**
     * Get aspects
     *
     * @return Aspect[]|\Doctrine\Common\Collections\Collection
     */
    public function getAspects()
    {
        return $this->aspects;
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }

    /**
     * @return string
     */
    public function getListName()
    {
        return $this->listName;
    }

    /**
     * @param string $listName
     */
    public function setListName($listName)
    {
        $this->listName = $listName;
    }

    /**
     * Set isJesuit
     *
     * @param boolean $isJesuit
     *
     * @return Person
     */
    public function setJesuit($isJesuit)
    {
        $this->isJesuit = $isJesuit;

        return $this;
    }

    /**
     * Get isJesuit
     *
     * @return boolean
     */
    public function isJesuit()
    {
        return $this->isJesuit;
    }

    /**
     * Set isJesuit
     *
     * @param boolean $isJesuit
     *
     * @return Person
     */
    public function setIsJesuit($isJesuit)
    {
        $this->isJesuit = $isJesuit;

        return $this;
    }

    /**
     * Get isJesuit
     *
     * @return boolean
     */
    public function getIsJesuit()
    {
        return $this->isJesuit;
    }

    /**
     * Add relationsIncoming
     *
     * @param \AppBundle\Entity\Relation $relationsIncoming
     *
     * @return Person
     */
    public function addRelationsIncoming(\AppBundle\Entity\Relation $relationsIncoming)
    {
        $this->relationsIncoming[] = $relationsIncoming;

        return $this;
    }

    /**
     * Remove relationsIncoming
     *
     * @param \AppBundle\Entity\Relation $relationsIncoming
     */
    public function removeRelationsIncoming(\AppBundle\Entity\Relation $relationsIncoming)
    {
        $this->relationsIncoming->removeElement($relationsIncoming);
    }

    /**
     * Get relationsIncoming
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRelationsIncoming()
    {
        return $this->relationsIncoming;
    }

    /**
     * Add relationsOutgoing
     *
     * @param \AppBundle\Entity\Relation $relationsOutgoing
     *
     * @return Person
     */
    public function addRelationsOutgoing(\AppBundle\Entity\Relation $relationsOutgoing)
    {
        $this->relationsOutgoing[] = $relationsOutgoing;

        return $this;
    }

    /**
     * Remove relationsOutgoing
     *
     * @param \AppBundle\Entity\Relation $relationsOutgoing
     */
    public function removeRelationsOutgoing(\AppBundle\Entity\Relation $relationsOutgoing)
    {
        $this->relationsOutgoing->removeElement($relationsOutgoing);
    }

    /**
     * Get relationsOutgoing
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRelationsOutgoing()
    {
        return $this->relationsOutgoing;
    }

    /**
     * Add place
     *
     * @param \AppBundle\Entity\Place $place
     *
     * @return Person
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
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPlaces()
    {
        return $this->places;
    }

    /**
     * Add source
     *
     * @param \AppBundle\Entity\Source $source
     *
     * @return Person
     */
    public function addSource(\AppBundle\Entity\Source $source)
    {
        $this->sources[] = $source;

        return $this;
    }

    /**
     * Remove source
     *
     * @param \AppBundle\Entity\Source $source
     */
    public function removeSource(\AppBundle\Entity\Source $source)
    {
        $this->sources->removeElement($source);
    }

    /**
     * Get sources
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSources()
    {
        return $this->sources;
    }

    /**
     * @return int
     */
    public function getLastMod()
    {
        return $this->lastMod;
    }

    /**
     * @param int $lastMod
     */
    public function setLastMod($lastMod)
    {
        $this->lastMod = $lastMod;
    }


}
