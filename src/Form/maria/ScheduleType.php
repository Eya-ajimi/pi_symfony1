<?php
namespace App\Form\maria;

use App\Entity\Schedule;
use App\Enum\DayOfWeek;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dayOfWeek', EnumType::class, [
                'class' => DayOfWeek::class,
                'choice_label' => fn(DayOfWeek $day) => $day->value,
                'attr' => ['class' => 'form-control']
            ])
            ->add('openingTime', TimeType::class, [
                'input' => 'datetime',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control time-input'],
                'required' => true
            ])
            ->add('closingTime', TimeType::class, [
                'input' => 'datetime',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control time-input'],
                'required' => true
            ])
            ->add('isClosed', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Closed on this day',
                'attr' => ['class' => 'form-check-input'],
                'data' => $options['is_closed']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Schedule::class,
            'is_closed' => false,
            'constraints' => [
                new Callback([$this, 'validateTimeRange'])
            ]
        ]);
    }

    public function validateTimeRange(Schedule $schedule, ExecutionContextInterface $context): void
    {
        // Skip validation if it's a closed day
        if ($schedule->getOpeningTime()->format('H:i') === '00:00' && 
            $schedule->getClosingTime()->format('H:i') === '00:00') {
            return;
        }

        if ($schedule->getOpeningTime() >= $schedule->getClosingTime()) {
            $context->buildViolation('Closing time must be after opening time')
                ->atPath('closingTime')
                ->addViolation();
        }
    }
}