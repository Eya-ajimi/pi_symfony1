<?php
// src/Form/ReclamationType.php
namespace App\Form;

use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Complaint Details',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please describe your complaint in detail...',
                    'rows' => 5
                ]
            ])
            ->add('nomshop', TextType::class, [
                'label' => 'Shop Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter the shop name'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Submit Complaint',
                'attr' => ['class' => 'btn-submit']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}