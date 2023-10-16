<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType; // 'format' => 'yyyy-MM-dd',
use Symfony\Component\Form\Extension\Core\Type\DateTimeType; // 'format' => 'yyyy-MM-dd',
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TherapySessionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('userTherapy')
            ->add('nOfTotal')
            ->add('status')
            ->add('startDate', DateTimeType::class, [
                'widget' => 'single_text',
                'invalid_message' => 'Your start date is not correct',
                'format' => 'yyyy-MM-dd\'T\'HH:mm:ssZZZZZ',
                'input' => 'datetime',
            ])
            ->add('startTime', DateTimeType::class, [
                'widget' => 'single_text',
                'invalid_message' => 'Your start time is not correct',
                'format' => 'yyyy-MM-dd\'T\'HH:mm:ssZZZZZ',
                'input' => 'datetime',
            ])
            //->add('musicPlaylist')
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\TherapySession',
            'csrf_protection' => false,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_therapy_session';
    }


}
