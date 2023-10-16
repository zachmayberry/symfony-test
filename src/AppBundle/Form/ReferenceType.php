<?php

namespace AppBundle\Form;

use AppBundle\Entity\Therapy;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType; // 'format' => 'yyyy-MM-dd',
use Symfony\Component\Form\Extension\Core\Type\DateTimeType; // 'format' => 'yyyy-MM-dd',
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReferenceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('category')
            ->add('title')
            ->add('teaser')
            ->add('imageName')
            ->add('croppedImageData')
            ->add('link')
            ->add('relatedTherapies', EntityType::class, [
                'class' => Therapy::class,
                'multiple' => true,
                'by_reference' => false,
                'choice_label' => 'title'
            ])
            ->add('date', DateTimeType::class, [
                'widget' => 'single_text',
                //'invalid_message' => 'Please enter a date in the following format: YYYY-MM-DD',
                //'format' => 'yyyy-MM-dd',
                'input' => 'datetime', // Accepted values are: "datetime", "string", "timestamp", "array".
            ])
            ->add('hidden')
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Reference',
            'csrf_protection' => false,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_reference';
    }


}
