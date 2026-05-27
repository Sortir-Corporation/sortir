<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\CampusRepository;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/{id}', name: 'user_')]
final class UserController extends AbstractController
{
    #[Route('', name: 'details', methods: ['GET'])]
    public function details(User $user, EventRepository $eventRepository): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        // 1. Autoriser l'admin ou le propriétaire
        if ($this->isGranted('ROLE_ADMIN') || $currentUser->getId() === $user->getId()) {
            return $this->render('user/details.html.twig', [
                'user' => $user,
            ]);
        }

        // 2. Vérifier l'existence d'un événement commun en BDD
        if (!$eventRepository->haveCommonEvent($currentUser, $user)) {
            throw $this->createAccessDeniedException("Vous ne pouvez consulter le profil d'un participant que si vous partagez un événement en commun.");
        }

        return $this->render('user/details.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/edit', name: 'edit', methods: ['GET', 'POST'])]
    // On injecte CampusRepository pour appeler le campus rattaché à l'utilisateur avec une boucle
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $em,
        CampusRepository $campusRepository,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        // 1. SÉCURITÉ DE BASE : Il faut être soit l'admin, soit le propriétaire du compte
        if (!$this->isGranted('ROLE_ADMIN') && $currentUser->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Accès interdit.');
        }

        // 2. SAUVEGARDE DE L'ÉTAT D'ORIGINE (Pour le cloisonnement des rôles)
        $originalActiveStatus = $user->isActive();
        $originalAlias = $user->getAlias();
        $originalFirstName = $user->getFirstName();
        $originalLastName = $user->getLastName();
        $originalPhoneNumber = $user->getPhoneNumber();
        $originalEmail = $user->getEmail();
        $originalCampus = $user->getCampus();
        $originalProfilePicture = $user->getProfilePicture();

        // 3. CRÉATION ET GESTION DU FORMULAIRE
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        // Si l'utilisateur a cliqué sur "MODIFIER."
        if ($form->isSubmitted() && $form->isValid()) {
            // CAS A : C'est le PROPRIÉTAIRE qui modifie son propre profil
            if ($currentUser->getId() === $user->getId()) {
                // Sécurité : Le propriétaire ne peut pas modifier son statut 'active'
                $user->setActive($originalActiveStatus);

                // Gestion de son nouveau mot de passe
                $newPassword = $form->get('password')->getData();
                if (!empty($newPassword)) {
                    $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                    $user->setPassword($hashedPassword);
                }
            }

            // CAS B : C'est l'ADMINISTRATEUR qui modifie le profil de quelqu'un d'autre
            if ($this->isGranted('ROLE_ADMIN') && $currentUser->getId() !== $user->getId()) {
                // Sécurité : L'admin ne peut modifier QUE le statut 'active'.
                // On écrase toutes les autres modifications en remettant les valeurs d'origine.
                $user->setAlias($originalAlias);
                $user->setFirstName($originalFirstName);
                $user->setLastName($originalLastName);
                $user->setPhoneNumber($originalPhoneNumber);
                $user->setEmail($originalEmail);
                $user->setCampus($originalCampus);
                $user->setProfilePicture($originalProfilePicture);

                // Note : On ne touche pas à $user->isActive(), on laisse la valeur soumise par l'admin.
            }

            $em->flush();

            $this->addFlash('success', 'Votre profil a bien été modifié');

            return $this->redirectToRoute('user_details', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'campuses' => $campusRepository->findAll(),
        ]);
    }

    #[Route('/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $em, Security $security): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        // SÉCURITÉ : Seul l'admin OU le propriétaire du compte peut supprimer ce profil
        if (!$this->isGranted('ROLE_ADMIN') && $currentUser->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException("Vous n'avez pas le droit de supprimer ce profil.");
        }

        // SÉCURITÉ : Vérification du token CSRF
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            // Déconnexion automatique si l'utilisateur supprime son propre compte
            if ($currentUser->getId() === $user->getId()) {
                $security->logout(false);
            }

            $em->remove($user);
            $em->flush();

            // Optionnel : Déconnecter l'utilisateur si c'est son propre compte qu'il supprime
            // $this->container->get('security.token_storage')->setToken(null);

            $this->addFlash('success', 'Le profil a bien été supprimé !');

            return $this->redirectToRoute('app_home');
        }

        $this->addFlash('error', 'Token CSRF invalide, suppression annulée.');

        return $this->redirectToRoute('user_details', ['id' => $user->getId()]);
    }

    //    #[IsGranted('ROLE_ADMIN')]
    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(Request $request, UserRepository $userRepository): Response
    {
        $search = $request->query->get('q');
        if ($search) {
            $users = $userRepository->findBySearch($search);
        } else {
            $users = $userRepository->findAll();
        }

        return $this->render('admin/list.html.twig', [
            'users' => $users,
            'search' => $search,
        ]);
    }
}
