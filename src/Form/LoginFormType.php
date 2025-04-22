<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;

class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'votre@email.com',
                    'autocomplete' => 'email'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre adresse email',
                    ]),
                    new Email([
                        'message' => 'Veuillez entrer une adresse email valide',
                        'mode' => 'html5'
                    ]),
                ],
            ])
            ->add('mot_de_passe', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '••••••••',
                    'autocomplete' => 'current-password'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre mot de passe',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',           //Ce champ s’appellera _csrf_token dans le HTML.
            'csrf_token_id' => 'authenticate',
            'csrf_message' => 'Jeton CSRF invalide, veuillez réessayer.',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'login_form';
    }
}