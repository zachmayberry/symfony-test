<?php

namespace AppBundle\Form;

use AppBundle\Entity\Therapy;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType; // 'format' => 'yyyy-MM-dd',
use Symfony\Component\Form\Extension\Core\Type\DateTimeType; // 'format' => 'yyyy-MM-dd',
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('user')
            ->add('title')
            ->add('teaser')
            ->add('content')
            ->add('imageName')
            ->add('croppedImageData')
            ->add('links')
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
            ->add('public')
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\News',
            'csrf_protection' => false,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_news';
    }


}
