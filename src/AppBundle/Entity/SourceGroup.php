<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

/**
 * Class SourceGroup
 * @ORM\Entity
 * @ORM\Table(indexes={@ORM\Index(name="slug", columns={"slug"})})
 */
class SourceGroup
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
    private $color;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $slug;

    /**
     * @var Source[]|Collection
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Source", mappedBy="sourceGroup")
//     * @ORM\OrderBy({"title" = "ASC"})
     */
    private $sources;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->sources = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return SourceGroup
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
     * Set color
     *
     * @param string $color
     *
     * @return SourceGroup
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return SourceGroup
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
     * Add source
     *
     * @param \AppBundle\Entity\Source $source
     *
     * @return SourceGroup
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
}
