<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * Class Source
 *
 * @ORM\Entity
 */
class Source
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $genre;

    /**
     * @var string
     * @ORM\Column(type="string", length=1000, nullable=true)
     */
    private $title;

    /**
     * @var string
     * @ORM\Column(type="string", length=1000, nullable=true)
     */
    private $seriesTitle;

    /**
     * @var string
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $authors;

    /**
     * @var string
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $editors;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $publisher;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $place;

    /**
     * @var string
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dateIssued;

    /**
     * @var string
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateCaptured;

    /**
     * @var string
     * @ORM\Column(type="string", length=1000, nullable=true)
     */
    private $url;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $note;

    /**
     * @var string
     * @ORM\Column(type="string", length=100000)
     */
    private $payload;

    /**
     * @var Person[]|Collection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Person", mappedBy="sources")
     * @Groups({"Source"})
     */
    private $associatedPersons;

    /**
     * @var Aspect[]|Collection
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Aspect", mappedBy="source")
     * @Groups({"Source"})
     */
    private $associatedAspects;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->associatedPersons = new \Doctrine\Common\Collections\ArrayCollection();
        $this->associatedAspects = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return Source
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
     * Set description
     *
     * @param string $description
     *
     * @return Source
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
     * Set payload
     *
     * @param string $payload
     *
     * @return Source
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * Get payload
     *
     * @return string
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Add associatedPerson
     *
     * @param \AppBundle\Entity\Person $associatedPerson
     *
     * @return Source
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
     * Add associatedAspect
     *
     * @param \AppBundle\Entity\Aspect $associatedAspect
     *
     * @return Source
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
     * Set genre
     *
     * @param string $genre
     *
     * @return Source
     */
    public function setGenre($genre)
    {
        $this->genre = $genre;

        return $this;
    }

    /**
     * Get genre
     *
     * @return string
     */
    public function getGenre()
    {
        return $this->genre;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Source
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
     * Set seriesTitle
     *
     * @param string $seriesTitle
     *
     * @return Source
     */
    public function setSeriesTitle($seriesTitle)
    {
        $this->seriesTitle = $seriesTitle;

        return $this;
    }

    /**
     * Get seriesTitle
     *
     * @return string
     */
    public function getSeriesTitle()
    {
        return $this->seriesTitle;
    }

    /**
     * Set authors
     *
     * @param string $authors
     *
     * @return Source
     */
    public function setAuthors($authors)
    {
        $this->authors = $authors;

        return $this;
    }

    /**
     * Get authors
     *
     * @return string
     */
    public function getAuthors()
    {
        return $this->authors;
    }

    /**
     * Set publisher
     *
     * @param string $publisher
     *
     * @return Source
     */
    public function setPublisher($publisher)
    {
        $this->publisher = $publisher;

        return $this;
    }

    /**
     * Get publisher
     *
     * @return string
     */
    public function getPublisher()
    {
        return $this->publisher;
    }

    /**
     * Set place
     *
     * @param string $place
     *
     * @return Source
     */
    public function setPlace($place)
    {
        $this->place = $place;

        return $this;
    }

    /**
     * Get place
     *
     * @return string
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * Set dateIssued
     *
     * @param integer $dateIssued
     *
     * @return Source
     */
    public function setDateIssued($dateIssued)
    {
        $this->dateIssued = $dateIssued;

        return $this;
    }

    /**
     * Get dateIssued
     *
     * @return integer
     */
    public function getDateIssued()
    {
        return $this->dateIssued;
    }

    /**
     * Set dateCaptured
     *
     * @param \DateTime $dateCaptured
     *
     * @return Source
     */
    public function setDateCaptured($dateCaptured)
    {
        $this->dateCaptured = $dateCaptured;

        return $this;
    }

    /**
     * Get dateCaptured
     *
     * @return \DateTime
     */
    public function getDateCaptured()
    {
        return $this->dateCaptured;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return Source
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set note
     *
     * @param string $note
     *
     * @return Source
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set editors
     *
     * @param string $editors
     *
     * @return Source
     */
    public function setEditors($editors)
    {
        $this->editors = $editors;

        return $this;
    }

    /**
     * Get editors
     *
     * @return string
     */
    public function getEditors()
    {
        return $this->editors;
    }
}
