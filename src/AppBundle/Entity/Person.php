<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

class Person
{
    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $pdrId;

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
     * @return string
     */
    public function getPdrId()
    {
        return $this->pdrId;
    }

    /**
     * @param string $pdrId
     */
    public function setPdrId($pdrId)
    {
        $this->pdrId = $pdrId;
    }


}
