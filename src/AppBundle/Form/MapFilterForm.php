<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class MapFilterForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('from', ChoiceType::class, [
                'choices' => array_combine(range(1500, 1700, 10),range(1500, 1700, 10))
            ]
            )
            ->add('to', ChoiceType::class, [
                'choices' => array_combine(range(1500, 1700, 10),range(1500, 1700, 10))
            ]
            )
            ->add('area', ChoiceType::class, [
                'label' => 'Include persons related with',
                'multiple' => true,
                'choices' => [
                    'Mathematics' => 'Mathematics',
                    'Geometry' => 'Geometry',
                    'Astronomy' => 'Astronomy'
                ]
            ]
            )
            ->add('type', ChoiceType::class, [
                'label' => 'Show only',
                'expanded' => true,
                'multiple' => true,
                'choices' => [
                    'Biographical' => 'biographical',
                    'Education' => 'education',
                    'teaching' => 'Teaching',
                    'other' => 'Other'
                ]
            ]
            )
        ;
    }

    public function getName()
    {
        return 'mapfilter';
    }
}
