<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class UserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('enabled')
            ->add('type')
            ->add('title')
            ->add('description')
            ->add('medicalScience')
            ->add('doctors')
            ->add('gender')
            ->add('disease')
            ->add('occupation')
            ->add('company')
            ->add('firstname')
            ->add('lastname')
            ->add('zipcode')
            ->add('location')
            ->add('country')
            ->add('plainPassword')
            ->add('phone')
            ->add('username')
            ->add('email')
            //->add('approved')
            ->add('hospital')
            ->add('dob', DateTimeType::class, [
                'widget' => 'single_text',
                //'invalid_message' => 'Please enter a date in the following format: YYYY-MM-DD',
                //'format' => 'yyyy-MM-dd',
                'input' => 'datetime', // Accepted values are: "datetime", "string", "timestamp", "array".
            ])
            ->add('certificateFileName')
            ->add('profileImageName')
            ->add('croppedProfileImageData')
            //->add('uploadedCertificateFile')
            //->add('uploadedProfileImageFile')
            /*
            ->add('certificateFile', VichFileType::class, [
                'required' => false,
                'allow_delete' => true, // optional, default is true
                'download_link' => true, // optional, default is true
                //'download_uri' => '...', // optional, if not provided - will automatically resolved using storage
            ])
            ->add('profileImageFile', VichFileType::class, [
                'required' => false,
                'allow_delete' => true, // optional, default is true
                'download_link' => true, // optional, default is true
                //'download_uri' => '...', // optional, if not provided - will automatically resolved using storage
            ])*/
            //->add('profileImageName')
            //->add('profileImageSize')
            //->add('updatedAt')
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\User',
            'csrf_protection' => false,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_user';
    }


}
