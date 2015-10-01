<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class AdvancedSearchForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text')
            ->add('subject', 'entity', array(
                'class' => 'AppBundle:Subject',
                'property' => 'title',
                'multiple' => true,
            ))
            ->add('place', 'text')

            ->add('includeAspects', 'checkbox', array(
                'label' => 'Include relevant aspects in result list',

            ))
            ->add('search', 'submit', array(
                'label' => 'Search',
                'attr' => array(
                    'class' => 'pull-right'
                )
            ))
        ;
    }

    public function getName()
    {
        return 'advanced_search';
    }

}
