<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class MapFilterForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('from', 'choice', array(
                'choices' => array_combine(range(1500, 1700, 10),range(1500, 1700, 10))
            ))
            ->add('to', 'choice', array(
                'choices' => array_combine(range(1500, 1700, 10),range(1500, 1700, 10))
            ))
            ->add('area', 'choice', array(
                'label' => 'Include persons related with',
                'expanded' => true,
                'multiple' => true,
                'choices' => array(
                    'Mathematics',
                    'Geometry',
                    'Astronomy'
                )
            ))
            ->add('type', 'choice', array(
                'label' => 'Show only',
                'expanded' => true,
                'multiple' => true,
                'choices' => array(
                    'Places of Birth',
                    'Places of Education',
                    'Places of Teaching',
                    'Other'
                )
            ))
        ;
    }

    public function getName()
    {
        return 'mapfilter';
    }
}
