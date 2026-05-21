<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{id}/user', name: 'user_')]
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
}
