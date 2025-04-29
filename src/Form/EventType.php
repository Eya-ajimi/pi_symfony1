<?php
// src/Form/EventType.php
namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomOrganisateur', TextType::class, [
                'label' => 'Shop Name',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Shop name cannot be empty'])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 5],
                'constraints' => [
                    new NotBlank(['message' => 'Description cannot be empty'])
                ]
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Start Date',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Start date cannot be empty'])
                ]
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'End Date',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'End date cannot be empty']),
                    new Callback([$this, 'validateDates'])
                ]
            ])
            ->add('emplacement', TextType::class, [
                'label' => 'Location',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Location cannot be empty'])
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Confirm',
                'attr' => ['class' => 'btn btn-primary mt-3']
            ])
            ->add('maxParticipants', IntegerType::class, [
                'required' => false,
                'label' => 'Maximum Participants',
                'attr' => [
                    'min' => 1,
                    'placeholder' => 'Leave empty for unlimited'
                ]
            ]);
    }

    public function validateDates($value, ExecutionContextInterface $context)
    {
        $form = $context->getRoot();
        $data = $form->getData();

        if ($data instanceof Event) {
            if ($data->getDateDebut() > $data->getDateFin()) {
                $context->buildViolation('End date must be after start date')
                    ->atPath('dateFin')
                    ->addViolation();
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'empty_data' => function (FormInterface $form) {
                return new Event(
                    $form->get('nomOrganisateur')->getData(),
                    $form->get('description')->getData(),
                    $form->get('dateDebut')->getData(),
                    $form->get('dateFin')->getData(),
                    $form->get('emplacement')->getData()
                );
            },
            'attr' => ['novalidate' => 'novalidate']
        ]);
    }
}