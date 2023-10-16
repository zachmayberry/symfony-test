<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TherapyType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type')
            ->add('title')
            ->add('recommendations')
            ->add('symptoms')
            ->add('relatedNews')
            ->add('relatedReferences')
            ->add('description')
            //->add('musicPlaylist')
            //->add('binauralPlaylist')
            ->add('dosage')
            ->add('rate')
            ->add('days')
            ->add('cycle')
            ->add('cycleType')
            ->add('user')
            ->add('public')
            ->add('published')
            ->add('parent')
            ->add('includesHq')
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Therapy',
            'csrf_protection' => false,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_therapy';
    }


}
