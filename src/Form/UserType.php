<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('alias')
            ->add('firstName')
            ->add('lastName')
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'id',
            ])
            ->add('phoneNumber')
            ->add('email')
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'required' => false,
                'mapped' => false, // pour avoir un champ vide
                'first_options' => [
                    'label' => 'Nouveau mot de passe :',
                    'attr' => [
                        'placeholder' => 'Laisser vide pour inchangé',
                        'autocomplete' => 'new-password', // <-- Force le navigateur à ne rien remplir
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe :',
                    'attr' => [
                        'placeholder' => 'Répétez le mot de passe',
                        'value' => '', // Force le champ HTML à être vide au chargement
                    ],
                ],
            ])

            ->add('active')
            ->add('profilePicture', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false, // 👈 TRÈS IMPORTANT : dit à Symfony de ne pas chercher la string en BDD directement pour ce champ
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'image PNG ou JPEG valide inférieure à 1024K',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
