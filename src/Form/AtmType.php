<?php

// src/Form/AtmType.php
namespace App\Form;

use App\Entity\Atm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AtmType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bankName', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Bank name cannot be empty']),
                    new Length(['max' => 255])
                ],
                'label' => 'Bank Name',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => 'active',
                    'Inactive' => 'inactive',
                ],
                'placeholder' => 'Choose status',
                'constraints' => [
                    new NotBlank(['message' => 'Status is required']),
                ],
                'label' => 'Status',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Atm::class,
        ]);
    }
}
