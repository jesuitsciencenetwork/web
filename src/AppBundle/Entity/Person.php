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
     * @ORM\Column(type="string", length=255)
     */
    private $firstName;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $lastName;

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
     * @var AlternateName[]|Collection
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\AlternateName", mappedBy="person")
     */
    private $alternateNames;

    /**
     * @var Subject[]|Collection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Subject")
     * @ORM\JoinTable()
     */
    private $subjects;

    /**
     * @var Aspect[]|Collection
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Aspect", mappedBy="person")
     */
    private $aspects;

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
        return $this->firstName . ' ' . $this->lastName;
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
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAspects()
    {
        return $this->aspects;
    }
}
