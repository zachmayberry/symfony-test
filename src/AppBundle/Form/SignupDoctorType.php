<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
//use Vich\UploaderBundle\Form\Type\VichFileType;

class SignupDoctorType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('medicalScience')
            ->add('gender')
            ->add('firstname')
            ->add('lastname')
            ->add('password')
            ->add('phone')
            ->add('email')
            ->add('hospital')
            //->add('certificateFileName')
            //->add('uploadedCertificateFile')
            /*
            ->add('certificateFile', VichFileType::class, [
                'required' => false,
                'allow_delete' => true, // optional, default is true
                'download_link' => true, // optional, default is true
                //'download_uri' => '...', // optional, if not provided - will automatically resolved using storage
            ])*/
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
        return 'appbundle_signupdoctor';
    }


}
