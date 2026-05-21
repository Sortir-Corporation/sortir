<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('alias', TextType::class, [
                'label' => 'Pseudo'])

            ->add('firstName', TextType::class, [
                'label' => 'Prénom'])

            ->add('lastName', TextType::class, [
                'label' => 'Nom'])

            ->add('phoneNumber', TextType::class, [
                'label' => 'Téléphone'])

            ->add('email', EmailType::class, [
                'label' => 'Email'])

            ->add('plainPassword', RepeatedType::class, options: [
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe doivent être identiques.',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => true,
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmer le mot de passe'],
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(
                        message: 'Entrer son mot de passe',
                    ),
                    new Length(
                        min: 6,
                        max: 4096,
                        // max length allowed by Symfony for security reasons
                        minMessage: 'Votre mot de passe doit avoir au moins 6 caractères',
                    ),
                ],
            ])

            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'name', // Le nom de la propriété dans l'entité Campus à afficher (ex: 'name', 'label'...)
                'placeholder' => 'Choisissez votre campus',
                'label' => 'Votre Campus',
                'required' => true,
            ])

            ->add('profilePicture', FileType::class, [
                'label' => 'Photo de profil (Fichier image)',
                'mapped' => false, // Ne pas l'associer directement à la propriété de l'entité
                'required' => false, // Rendre le champ optionnel
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG ou WEBP)',
                    ]),
                ],
            ])

            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J\'accepte les termes et conditions',
                'mapped' => false,
                'constraints' => [
                    new IsTrue(
                        message: 'You should agree to our terms.',
                    ),
                ],
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
