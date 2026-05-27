<?php

namespace App\Controller;

use App\Entity\Location;
use App\Form\LocationType;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('location', name: 'location_')]
final class LocationController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(LocationRepository $locationRepository, Request $request): Response
    {
        // 1. On récupère la recherche (?q=...)
        $search = $request->query->get('l');

        // 2. On filtre si une recherche est lancée, sinon on prend tout
        if ($search) {
            $locations = $locationRepository->findBySearch($search);
        } else {
            $locations = $locationRepository->findAll();
        }

        return $this->render('location/list.html.twig', [
            'locations' => $locations,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $location = new Location();
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($location);
            $entityManager->flush();

            $this->addFlash('success', 'Le lieu a bien été créé');

            return $this->redirectToRoute('location_list');
        }

        return $this->render('location/new.html.twig', [
            'locationForm' => $form->createView(),
            'title' => 'Ajout d\'une lieu',
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]

    public function edit(Location $location, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le lieu a bien été modifié');

            return $this->redirectToRoute('location_list');
        }

        return $this->render('location/edit.html.twig', [
            'locationForm' => $form->createView(),
            'location' => $location,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Location $location, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($location);
        $entityManager->flush();
        $this->addFlash('success', 'Le lieu a bien été supprimé');

        return $this->redirectToRoute('location_list');
    }
}
