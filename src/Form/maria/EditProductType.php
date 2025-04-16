<?php

namespace App\Form\maria;

use App\Entity\Produit;
use App\Entity\Discount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\File;


class EditProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class)
            ->add('description', TextareaType::class, ['required' => false])
            ->add('prix', NumberType::class, [
                'html5' => true,
                'attr' => ['step' => '0.01', 'min' => '0']
            ])
            ->add('stock', NumberType::class)
            // ... other fields ...
            // In EditProductType.php
            ->add('promotionId', EntityType::class, [  // Changed from 'discount' to 'promotionId'
                'class' => Discount::class,
                'choice_label' => function (Discount $discount) {
                    return $discount->getDiscountPercentage() . '%';
                },
                'choices' => $options['promotionId'] ?? [],
                'required' => false,
                'placeholder' => 'No discount'
            ])
            ->add('image_url', FileType::class, [
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image file',
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
            'shopId' => null,
            'promotionId' => [] // Add this default value
        ]);
    }
}