<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class AdvancedSearchForm extends AbstractType
{
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
                )
            ))

            ->add('birthDate', 'choice', array(
                'required' => false,
                'placeholder' => '',
                'choices' => array_combine(range(1600, 1800), range(1600, 1800)),
            ))

            ->add('deathDateOperator', 'choice', array(
                'choices' => array(
                    'before' => 'before',
                    'after' => 'after',
                    'in' => 'in'
                )
            ))

            ->add('deathDate', 'choice', array(
                'required' => false,
                'placeholder' => '',
                'choices' => array_combine(range(1600, 1800), range(1600, 1800)),
            ))

            ->add('position', 'text', array(
                'required' => false
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

}
