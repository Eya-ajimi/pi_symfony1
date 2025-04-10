<?php

// src/Form/ReplyType.php

namespace App\Form;

use App\Entity\SousCommentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ReplyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('contenu', TextType::class, [  // Changed from 'content' to 'contenu'
                'label' => false,
                'attr' => ['placeholder' => 'Write a reply...'],
                'constraints' => [
                    new NotBlank(['message' => 'Reply cannot be empty']),
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