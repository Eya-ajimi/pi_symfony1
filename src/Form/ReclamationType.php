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
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Complaint Details',
                'required' => true, // Explicitly mark as required
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir une description de la réclamation.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Décrivez votre réclamation en détail...',
                    'rows' => 5,
                ],
            ])
            ->add('nomshop', TextType::class, [
                'label' => 'Shop Name',
                'required' => true, // Explicitly mark as required
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir le nom du magasin.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le nom du magasin',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Soumettre la réclamation',
                'attr' => ['class' => 'btn-submit'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
            'attr' => ['novalidate' => 'novalidate'], 
        ]);
    }
}