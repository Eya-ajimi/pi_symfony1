<?php
// src/Form/ParkingSpotType.php
namespace App\Form;

use App\Entity\PlaceParking;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ParkingSpotType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('zone', TextType::class, [
                'label' => 'Zone',
                'attr' => [
                    'pattern' => '[A-Za-z]',
                    'title' => 'Single letter only',
                    'class' => 'form-control'
                ]
            ])
            ->add('floor', ChoiceType::class, [
                'label' => 'Floor',
                'choices' => [
                    'Floor 1' => 'Level 1',
                    'Floor 2' => 'Level 2',
                    'Floor 3' => 'Level 3',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Free' => 'free',
                    'Taken' => 'taken',
                    'Reserved' => 'reserved',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PlaceParking::class,
        ]);
    }
}