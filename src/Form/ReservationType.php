<?php
// src/Form/ReservationType.php
namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateReservation', DateTimeType::class, [
                'label' => 'Start Time',
                'widget' => 'single_text',
                'html5' => true,
                'constraints' => [
                    new GreaterThan([
                        'value' => 'now',
                        'message' => 'Start date must be in the future'
                    ])
                ]
            ])
            ->add('dateExpiration', DateTimeType::class, [
                'label' => 'End Time',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('vehicleType', ChoiceType::class, [
                'label' => 'Vehicle Type',
                'choices' => [
                    'Motorcycle' => 'Motorcycle',
                    'Compact' => 'Compact',
                    'SUV' => 'SUV',
                    'Van' => 'Van',
                ],
            ])
            ->add('carWashType', ChoiceType::class, [
                'label' => 'Car Wash Service',
                'required' => false,
                'choices' => [
                    'None' => 'None',
                    'Basic' => 'Basic',
                    'Premium' => 'Premium',
                    'Deluxe' => 'Deluxe',
                ],
            ])
            ->add('price', HiddenType::class, [
                'data' => 0.00,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Additional Notes',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Confirm Reservation',
                'attr' => ['class' => 'btn btn-primary'],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Upload Image (for car wash notes)',
                'required' => false,
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'file-upload-input'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, GIF)',
                    ])
                ],
            ]);

        // Add form-level validation
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $reservation = $event->getData();
                
                if (!$reservation instanceof Reservation) {
                    return;
                }

                if ($reservation->getDateReservation() >= $reservation->getDateExpiration()) {
                    $form->get('dateExpiration')->addError(new FormError(
                        'End date must be after start date'
                    ));
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'constraints' => [
                new Callback([$this, 'validateDates']),
            ],
        ]);
    }

    public function validateDates(Reservation $reservation, ExecutionContextInterface $context)
    {
        if ($reservation->getDateReservation() >= $reservation->getDateExpiration()) {
            $context->buildViolation('End date must be after start date')
                ->atPath('dateExpiration')
                ->addViolation();
        }
    }
}