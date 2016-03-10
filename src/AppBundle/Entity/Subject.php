<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\Groups;

/**
 * Class Subject
 * @ORM\Entity
 * @ORM\Table(indexes={@ORM\Index(name="slug", columns={"slug"})})
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
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $slug;

    /**
     * @var Person[]|Collection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Person", mappedBy="subjects")
     * @Groups({"Subject"})
     */
    private $associatedPersons;

    /**
     * @var Aspect[]|Collection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Aspect", mappedBy="subjects")
     * @Groups({"Subject"})
     */
    private $associatedAspects;

    /**
     * @var SubjectGroup[]|Collection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\SubjectGroup", mappedBy="subjects")
     * @Groups({"Subject"})
     */
    private $subjectGroups;

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

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return Subject
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Add associatedAspect
     *
     * @param \AppBundle\Entity\Aspect $associatedAspect
     *
     * @return Subject
     */
    public function addAssociatedAspect(\AppBundle\Entity\Aspect $associatedAspect)
    {
        $this->associatedAspects[] = $associatedAspect;

        return $this;
    }

    /**
     * Remove associatedAspect
     *
     * @param \AppBundle\Entity\Aspect $associatedAspect
     */
    public function removeAssociatedAspect(\AppBundle\Entity\Aspect $associatedAspect)
    {
        $this->associatedAspects->removeElement($associatedAspect);
    }

    /**
     * Get associatedAspects
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAssociatedAspects()
    {
        return $this->associatedAspects;
    }

    /**
     * Add subjectGroup
     *
     * @param \AppBundle\Entity\SubjectGroup $subjectGroup
     *
     * @return Subject
     */
    public function addSubjectGroup(\AppBundle\Entity\SubjectGroup $subjectGroup)
    {
        $this->subjectGroups[] = $subjectGroup;

        return $this;
    }

    /**
     * Remove subjectGroup
     *
     * @param \AppBundle\Entity\SubjectGroup $subjectGroup
     */
    public function removeSubjectGroup(\AppBundle\Entity\SubjectGroup $subjectGroup)
    {
        $this->subjectGroups->removeElement($subjectGroup);
    }

    /**
     * Get subjectGroups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSubjectGroups()
    {
        return $this->subjectGroups;
    }
}
