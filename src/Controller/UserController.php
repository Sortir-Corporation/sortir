<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\CampusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('user/{id}', name: 'user_')]
final class UserController extends AbstractController
{
    #[Route('', name: 'details')]
    public function details(User $user): Response
    {
        // Seul l'utilisateur connecté puisse voir son propre profil
//        $this->denyAccessUnlessGranted('SAME_USER', $user);

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
        UserPasswordHasherInterface $passwordHasher
    ): Response {

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        // Si l'utilisateur a cliqué sur "MODIFIER"
        if ($form->isSubmitted() && $form->isValid()) {

            // 2. On récupère la valeur du champ password
            $newPassword = $form->get('password')->getData();

            if (!empty($newPassword)) {
                // Si un nouveau mot de passe est saisi, on le hache et on l'assigne
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
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
    public function delete(Request $request, User $user, EntityManagerInterface $em): Response
    {
        // SÉCURITÉ : Vérification du token CSRF
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
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

}







