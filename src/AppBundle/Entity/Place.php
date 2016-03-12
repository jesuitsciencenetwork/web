<?php

namespace AppBundle\Entity;

use AppBundle\Helper;
use AppBundle\LetterListInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\Groups;

/**
 * Class Place
 * @ORM\Entity()
 * @ORM\Table(indexes={@ORM\Index(name="place_name", columns={"place_name"}),@ORM\Index(name="slug", columns={"slug"})})
 */
class Place implements LetterListInterface
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
    private $placeName;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $slug;

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

    /**
     * @var Person[]|Collection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Person", mappedBy="places")
     * @Groups({"Place"})
     */
    private $associatedPersons;

    /**
     * @var Aspect[]|Collection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Aspect", mappedBy="places")
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set placeName
     *
     * @param string $placeName
     *
     * @return Place
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
     * Set slug
     *
     * @param string $slug
     *
     * @return Place
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
     * Set country
     *
     * @param string $country
     *
     * @return Place
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set continent
     *
     * @param string $continent
     *
     * @return Place
     */
    public function setContinent($continent)
    {
        $this->continent = $continent;

        return $this;
    }

    /**
     * Get continent
     *
     * @return string
     */
    public function getContinent()
    {
        return $this->continent;
    }

    /**
     * Set latitude
     *
     * @param string $latitude
     *
     * @return Place
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
     * @return Place
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
     * Add associatedPerson
     *
     * @param \AppBundle\Entity\Person $associatedPerson
     *
     * @return Place
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
     * @return Place
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

    public function getMarkerLabel()
    {

    }

    public function getLetter()
    {
        return strtoupper(substr(Helper::removeAccents(
            "'s-Hertogenbosch" === $this->placeName ? 'H' : $this->placeName
        ), 0, 1));
    }
}
