<?php

namespace TrackersBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserDetailType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('user_id')
            ->add('situation')
            ->add('firstname')
            ->add('lastname')
            ->add('avatar')
            ->add('street1')
            ->add('street2')
            ->add('state')
            ->add('city')
            ->add('phone')
            ->add('country')
            ->add('modified')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'TrackersBundle\Entity\UserDetail'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'trackersbundle_userdetail';
    }
}
