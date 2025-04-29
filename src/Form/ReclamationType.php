<?php
// src/Form/ReclamationType.php
namespace App\Form;

use App\Entity\Reclamation;
use App\Validator\Constraints\NoBadWords;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Complaint Details',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a description of your complaint.',
                    ]),
                    new NoBadWords(),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Describe your complaint in detail...',
                    'rows' => 5,
                ],
            ])
            ->add('nomshop', TextType::class, [
                'label' => 'Shop Name',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter the shop name.',
                    ]),
                    new NoBadWords(),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter the shop name',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => '<i class="fas fa-paper-plane"></i> Submit Complaint',
                'label_html' => true, // Important! Allows HTML inside label
                'attr' => ['class' => 'btn-submit'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}