<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

/**
 * Class Subject
 * @ORM\Entity
 */
class Subject
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @var Person[]|Collection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Person")
     */
    private $associatedPersons;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->associatedPersons = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set title
     *
     * @param string $title
     *
     * @return Subject
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
     * Add associatedPerson
     *
     * @param \AppBundle\Entity\Person $associatedPerson
     *
     * @return Subject
     */
    public function addAssociatedPerson(\AppBundle\Entity\Person $associatedPerson)
    {
        $this->associatedPersons[] = $associatedPerson;

        return $this;
    }

    /**
     * Remove associatedPerson
     *
     * @param \AppBundle\Entity\Person $associatedPerson
     */
    public function removeAssociatedPerson(\AppBundle\Entity\Person $associatedPerson)
    {
        $this->associatedPersons->removeElement($associatedPerson);
    }

    /**
     * Get associatedPersons
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAssociatedPersons()
    {
        return $this->associatedPersons;
    }
}
