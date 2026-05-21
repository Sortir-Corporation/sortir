<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // 1. Gestion de l'upload de la photo de profil
            /** @var $pictureFile */
            $pictureFile = $form->get('profilePicture')->getData();

            if ($pictureFile) {
                // Générer un nom de fichier unique (ex: 65f3a2b1c4d5e.jpg)
                $newFilename = uniqid().'.'.$pictureFile->guessExtension();

                try {
                    // Déplacer le fichier dans le dossier public/uploads/profiles
                    $pictureFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/profiles',
                        $newFilename
                    );

                    // On stocke le nom du fichier dans l'entité User
                    $user->setProfilePicture($newFilename);
                } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $e) {
                    // Gérer l'erreur si le fichier ne se déplace pas (droits d'écriture, etc.)
                    $this->addFlash('error', 'Erreur lors de l\'enregistrement de l\'image.');
                }
            }

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email

            return $this->redirectToRoute('_profiler_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
