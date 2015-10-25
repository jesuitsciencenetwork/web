<?php

namespace AppBundle\Entity;

use AppBundle\Helper;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

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
    private $firstName;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastName;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nameLink;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;

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
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dateOfBirth;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dateOfDeath;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $placeOfBirth;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $placeOfDeath;

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

    /**
     * Set firstName
     *
     * @param string $firstName
     *
     * @return Person
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     *
     * @return Person
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    public function getDisplayName()
    {
        $name = '';

        if ($this->title) {
            $name .= $this->title . ' ';
        }
        if ($this->firstName) {
            $name .= $this->firstName . ' ';
        }
        if ($this->nameLink) {
            if (substr($this->nameLink, -1, 1) == "'") {
                $name .= $this->nameLink;
            } else {
                $name .= $this->nameLink . ' ';
            }
        }
        if ($this->lastName) {
            $name .= $this->lastName;
        }

        return trim($name);
    }

    public function getListName()
    {
        $name = '';

        if ($this->lastName) {
            $name .= $this->lastName . ', ';
        }

        if ($this->title) {
            $name .= $this->title . ' ';
        }
        if ($this->firstName) {
            $name .= $this->firstName . ' ';
        }
        if ($this->nameLink) {
            $name .= $this->nameLink;
        }

        return trim($name);
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
     * Set title
     *
     * @param string $title
     *
     * @return Person
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
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
     * Set nameLink
     *
     * @param string $nameLink
     *
     * @return Person
     */
    public function setNameLink($nameLink)
    {
        $this->nameLink = $nameLink;

        return $this;
    }

    /**
     * Get nameLink
     *
     * @return string
     */
    public function getNameLink()
    {
        return $this->nameLink;
    }

    /**
     * Set placeOfBirth
     *
     * @param string $placeOfBirth
     *
     * @return Person
     */
    public function setPlaceOfBirth($placeOfBirth)
    {
        $this->placeOfBirth = $placeOfBirth;

        return $this;
    }

    /**
     * Get placeOfBirth
     *
     * @return string
     */
    public function getPlaceOfBirth()
    {
        return $this->placeOfBirth;
    }

    /**
     * Set placeOfDeath
     *
     * @param string $placeOfDeath
     *
     * @return Person
     */
    public function setPlaceOfDeath($placeOfDeath)
    {
        $this->placeOfDeath = $placeOfDeath;

        return $this;
    }

    /**
     * Get placeOfDeath
     *
     * @return string
     */
    public function getPlaceOfDeath()
    {
        return $this->placeOfDeath;
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
}
