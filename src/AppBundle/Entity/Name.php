<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Name
 * @ORM\Entity
 */
class Name
{
    private $name;

    /**
     * @var Person
     */
    private $person;
}
