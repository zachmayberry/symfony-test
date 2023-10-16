<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Voryx\RESTGeneratorBundle\Form\Type\VoryxEntityType;

class TrackType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type')
            ->add('toneType')
            ->add('sourceAudioId')
            ->add('title')
            ->add('artists')
            ->add('album')
            ->add('description')
            ->add('duration')
            ->add('fLeft')
            ->add('fRight')
            ->add('ampValue')
            ->add('ampMod')
            ->add('symptoms')
            ->add('compileStatus')
            ->add('genre')
            ->add('fileName')
            ->add('uploadedFile')
            ->add('moods')
            ->add('dosage')
            ->add('therapyLength')
            ->add('composers')
            ->add('publishers')
            ->add('recommendations')
            ->add('includesHq')
            ->add('fLeftHq')
            ->add('fRightHq')
            ->add('ampValueHq')
            ->add('ampModHq')
            ->add('fileNameHq')
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Track',
            'csrf_protection' => false,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_track';
    }


}
