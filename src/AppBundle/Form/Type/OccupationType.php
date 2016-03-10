<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OccupationType extends AbstractType {

    public function getName()
    {
        return 'occupation';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
            'multiple' => true,
            'attr' => [
                'class' => 'selectpicker',
                'data-live-search' => 'true'
            ]
            ]
        );
    }

    public function getParent()
    {
        return 'choice';
    }

}
