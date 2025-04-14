<?php

// src/Form/ReplyType.php

namespace App\Form;

use App\Entity\SousCommentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Regex;
class ReplyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('contenu', TextType::class, [  // Changed from 'content' to 'contenu'
                'label' => false,
                'attr' => ['placeholder' => 'Write a reply...'],
                'constraints' => [
                    new NotBlank(['message' => 'reply content cannot be empty!']),
                    new Regex([
                        'pattern' => '/\b(shit|fuck|asshole|bitch|dumb|ass|pussy|putain|con|drugs|saloup)\b/i',
                        'match' => false,
                        'message' => 'Your reply contains inappropriate language'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SousCommentaire::class,
        ]);
    }
}