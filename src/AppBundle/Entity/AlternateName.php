<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * Class Name
 * @ORM\Entity
 */
class AlternateName
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @var Person
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person", inversedBy="alternateNames")
     * @Groups({"AlternateName"})
     */
    private $person;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $displayName;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * @param Person $person
     */
    public function setPerson($person)
    {
        $this->person = $person;
    }

    /**
     * Set displayName
     *
     * @param string $displayName
     *
     * @return AlternateName
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Get displayName
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }
}
