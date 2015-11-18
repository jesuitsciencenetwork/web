<?php

namespace AppBundle\Form;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
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
            ->add('resultDisplay', 'choice', array(
                'multiple' => false,
                'expanded' => true,
                'choices' => array(
                    'person' => 'Persons',
                    'subject' => 'Subjects',
                    'places' => 'Places'
                )
            ))
            ->add('name', 'text', array(
                'required' => false,
            ))
            ->add('subject', 'entity', array(
                'class' => 'AppBundle:Subject',
                'property' => 'title',
                'multiple' => true,
                'required' => false,
                'attr' => array(
                    'class' => 'selectpicker',
                    'data-live-search' => 'true'
                )
            ))

            ->add('place', 'text', array(
                'required' => false,
            ))
            ->add('placeRestriction', 'choice', array(
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => array(
                    'bio' => 'Biographical events',
                    'career' => 'Career events',
                    'teaching' => 'Teaching events'
                )
            ))

            ->add('birthDateOperator', 'choice', array(
                'choices' => array(
                    'before' => 'before',
                    'after' => 'after',
                    'in' => 'in'
                ),
                'expanded' => true
            ))

            ->add('birthDate', 'text', array(
                'required' => false,
            ))

            ->add('deathDateOperator', 'choice', array(
                'choices' => array(
                    'before' => 'before',
                    'after' => 'after',
                    'in' => 'in'
                ),
                'expanded' => true
            ))

            ->add('deathDate', 'text', array(
                'required' => false,
            ))

            ->add('occupation', 'choice', array(
                'required' => false,
                'choices' => $this->getOccupationChoices(),
                'multiple' => true,
                'attr' => array(
                   'class' => 'selectpicker',
                    'data-live-search' => 'true'
                )
            ))

            ->add('membership', 'text', array(
                'required' => false
            ))

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

        $choices = array();
        foreach ($results as $row) {
            $choices[$row['occupationSlug']] = $row['occupation'];
        }

        return $choices;
    }
}
