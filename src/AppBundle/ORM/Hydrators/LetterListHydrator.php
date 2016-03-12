<?php

namespace AppBundle\ORM\Hydrators;

use AppBundle\LetterListInterface;
use Doctrine\ORM\Internal\Hydration\HydrationException;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator;
use PDO;

class LetterListHydrator extends ObjectHydrator
{
    protected function hydrateAllData()
    {
        $data = parent::hydrateAllData();

        $letters = [];

        foreach ($data as $item) {
            if (!($item instanceof LetterListInterface)) {
                throw new HydrationException('LetterListHydrator can only be used with LetterListInterface objects');
            }

            $letter = $item->getLetter();
            if (!array_key_exists($letter, $letters)) {
                $letters[$letter] = [];
            }
            $letters[$letter][] = $item;
        }

        ksort($letters);

        return $letters;
    }
}
