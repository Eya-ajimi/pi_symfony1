<?php

namespace App\Form\maria;

use App\Entity\Discount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class DiscountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('discountPercentage', NumberType::class, [
                'label' => 'Discount Percentage',
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                    'step' => 0.01
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a discount percentage',
                    ]),
                    new Type([
                        'type' => 'numeric',
                        'message' => 'The value {{ value }} is not a valid number',
                    ]),
                    new Range([
                        'min' => 0,
                        'max' => 100,
                        'notInRangeMessage' => 'Discount percentage must be between {{ min }}% and {{ max }}%',
                        'invalidMessage' => 'Discount percentage must be a valid number',
                    ])
                ]
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a start date',
                    ]),
                    new LessThan([
                        'propertyPath' => 'parent.all[endDate].data',
                        'message' => 'Start date must be before end date'
                    ])
                ]
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select an end date',
                    ]),
                    new GreaterThan([
                        'propertyPath' => 'parent.all[startDate].data',
                        'message' => 'End date must be after start date'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Discount::class,
        ]);
    }
}