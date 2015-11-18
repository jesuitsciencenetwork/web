<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OccupationType extends AbstractType {

    private $em;

    public function getName()
    {
        return 'occupation';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(array(
            'multiple' => true,
            'attr' => array(
                'class' => 'selectpicker',
                'data-live-search' => 'true'
            )
        ));
    }

    public function getParent()
    {
        return 'choice';
    }

}
