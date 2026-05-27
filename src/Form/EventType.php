<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Location;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('startTime')
            ->add('duration')
            ->add('registrationDeadline')
            ->add('maxParticipants')
            ->add('eventInfo')
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'placeholder' => 'Choisir un lieu...',
                'choice_label' => 'name',
            ])

            ->add('eventPicture', FileType::class, [
                'label' => 'Event Picture',
                'mapped' => false, // 👈 TRÈS IMPORTANT : dit à Symfony de ne pas chercher la string en BDD directement pour ce champ
                'required' => false,
                'attr' => [
                    'accept' => 'image/jpeg, image/png',
                ],
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
            'data_class' => Event::class,
        ]);
    }
}
