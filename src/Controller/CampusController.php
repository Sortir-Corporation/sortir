<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Form\CampusType;
use App\Repository\CampusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('campus', name: 'campus_')]
final class CampusController extends AbstractController
{
    #[Route('', name: 'campus_list', methods: ['GET'])]
    public function list(CampusRepository $campusRepository, Request $request): Response
    {
        // 1. On récupère la recherche (?q=...)
        $search = $request->query->get('q');

        // 2. On filtre si une recherche est lancée, sinon on prend tout
        if ($search) {
            $campus = $campusRepository->findBySearch($search);
        } else {
            $campus = $campusRepository->findAll();
        }

        return $this->render('campus/list.html.twig', [
            'campus' => $campus,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $campus = new Campus();
        $form = $this->createForm(CampusType::class, $campus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($campus);
            $em->flush();

            $this->addFlash('success', 'Campus créé avec succès');

            return $this->redirectToRoute('campus_campus_list');
        }

        return $this->render('campus/new.html.twig', [
            'form' => $form->createView(),
            'title' => 'Créer un campus',
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Campus $campus, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CampusType::class, $campus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Campus modifié avec succès');

            return $this->redirectToRoute('campus_campus_list');
        }

        return $this->render('campus/edit.html.twig', [
            'form' => $form->createView(),
            'campus' => $campus,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Campus $campus, EntityManagerInterface $em): Response
    {
        $em->remove($campus);
        $em->flush();
        $this->addFlash('success', 'Campus supprimé');

        return $this->redirectToRoute('campus_campus_list');
    }
}
