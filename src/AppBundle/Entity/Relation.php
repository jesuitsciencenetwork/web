<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\UniqueConstraint;
use JMS\Serializer\Annotation\Groups;

/**
 * Class Subject
 * @ORM\Entity
 * @ORM\Table(name="relations", uniqueConstraints={@UniqueConstraint(name="unique_relation", columns={"source_id", "target_id", "class", "context", "value"})})
 */
class Relation
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $id;

    /**
     * @var Person[]|Collection
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person", inversedBy="relationsOutgoing")
     * @Groups({"Relation"})
     */
    private $source;

    /**
     * @var Person[]|Collection
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person", inversedBy="relationsIncoming")
     * @Groups({"Relation"})
     */
    private $target;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $class;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $context;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $value;

    /**
     * @var Aspect
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Aspect", inversedBy="relations")
     */
    private $aspect;

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
     * Set class
     *
     * @param string $class
     *
     * @return Relation
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set context
     *
     * @param string $context
     *
     * @return Relation
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get context
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return Relation
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set source
     *
     * @param \AppBundle\Entity\Person $source
     *
     * @return Relation
     */
    public function setSource(\AppBundle\Entity\Person $source = null)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return \AppBundle\Entity\Person
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set target
     *
     * @param \AppBundle\Entity\Person $target
     *
     * @return Relation
     */
    public function setTarget(\AppBundle\Entity\Person $target = null)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target
     *
     * @return \AppBundle\Entity\Person
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set aspect
     *
     * @param \AppBundle\Entity\Aspect $aspect
     *
     * @return Relation
     */
    public function setAspect(\AppBundle\Entity\Aspect $aspect = null)
    {
        $this->aspect = $aspect;

        return $this;
    }

    /**
     * Get aspect
     *
     * @return \AppBundle\Entity\Aspect
     */
    public function getAspect()
    {
        return $this->aspect;
    }
}
