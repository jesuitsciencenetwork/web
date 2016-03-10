<?php

namespace AppBundle\Form;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class AdvancedSearchForm extends AbstractType
{
    private $em;

    public function __construct(ObjectManager $em)
    {
        $this->em = $em;

    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('resultDisplay', 'choice', [
                'multiple' => false,
                'expanded' => true,
                'choices' => [
                    'person' => 'Persons',
                    'subject' => 'Subjects',
                    'places' => 'Places'
                ]
            ]
            )
            ->add('name', 'text', [
                'required' => false,
            ]
            )
            ->add('subject', 'entity', [
                'class' => 'AppBundle:Subject',
                'property' => 'title',
                'multiple' => true,
                'required' => false,
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true'
                ]
            ]
            )

            ->add('place', 'text', [
                'required' => false,
            ]
            )
            ->add('placeRestriction', 'choice', [
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'bio' => 'Biographical events',
                    'career' => 'Career events',
                    'teaching' => 'Teaching events'
                ]
            ]
            )

            ->add('birthDateOperator', 'choice', [
                'choices' => [
                    'before' => 'before',
                    'after' => 'after',
                    'in' => 'in'
                ],
                'expanded' => true
            ]
            )

            ->add('birthDate', 'text', [
                'required' => false,
            ]
            )

            ->add('deathDateOperator', 'choice', [
                'choices' => [
                    'before' => 'before',
                    'after' => 'after',
                    'in' => 'in'
                ],
                'expanded' => true
            ]
            )

            ->add('deathDate', 'text', [
                'required' => false,
            ]
            )

            ->add('occupation', 'choice', [
                'required' => false,
                'choices' => $this->getOccupationChoices(),
                'multiple' => true,
                'attr' => [
                   'class' => 'selectpicker',
                    'data-live-search' => 'true'
                ]
            ]
            )

            ->add('membership', 'text', [
                'required' => false
            ]
            )

        ;
    }

    public function getName()
    {
        return 'advanced_search';
    }

    private function getOccupationChoices()
    {
        $results = $this->em->createQuery('SELECT a.occupation, a.occupationSlug FROM AppBundle:Aspect a WHERE a.occupation IS NOT NULL GROUP BY a.occupation ORDER BY a.occupation ASC')
            ->getResult(Query::HYDRATE_ARRAY);

        $choices = [];
        foreach ($results as $row) {
            $choices[$row['occupationSlug']] = $row['occupation'];
        }

        return $choices;
    }
}
