<?php

// src/Form/CommentType.php
namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Regex;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('contenu', TextType::class, [
                'label' => false,
                'attr' => ['placeholder' => 'Write a comment...', 'rows' => 2],
                'constraints' => [
                    new NotBlank(['message' => '']),
                    new Regex([
                        'pattern' => '/\b(shit|fuck|asshole|bitch|dumb|ass|pussy|putain|con|merdre|saloup)\b/i',
                        'match' => false,
                        'message' => 'Your comment contains inappropriate language'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,
        ]);
    }
}
