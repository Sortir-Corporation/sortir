<?php

namespace App\Controller;

use App\Entity\City;
use App\Form\CityType;
use App\Repository\CityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cities', name: 'cities_')]
final class CityController extends AbstractController
{
    // Afficher la liste
    #[Route('', name: 'city_list', methods: ['GET'])]
    public function list(CityRepository $citiesRepository, Request $request): Response
    {
        // 1. On récupère la recherche (?q=...)
        $search = $request->query->get('q');

        // 2. On filtre si une recherche est lancée, sinon on prend tout
        if ($search) {
            $cities = $citiesRepository->findBySearch($search);
        } else {
            $cities = $citiesRepository->findAll();
        }

        return $this->render('city/list.html.twig', [
            'cities' => $cities,
        ]);
    }

    // Créer une ville
    #[Route('/create', name: 'city_new', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $city = new City();
        $form = $this->createForm(CityType::class, $city);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($city);
            $entityManager->flush();

            $this->addFlash('success', 'La ville a bien été ajoutée');

            return $this->redirectToRoute('cities_city_list');
        }

        return $this->render('city/new.html.twig', [
            'city' => $city,
            'form' => $form,
        ]);
    }

    // Modifier une ville
    #[Route('/{id}/edit', name: 'city_edit', methods: ['GET', 'POST'])] // 👈 ID dans l'URL
    public function edit(Request $request, City $city, EntityManagerInterface $entityManager): Response
    {
        // Ici, Symfony injecte directement la $city correspondante à l'ID de l'URL
        $form = $this->createForm(CityType::class, $city);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Pas besoin de ->persist() lors d'une modification, Doctrine "surveille" déjà l'entité
            $entityManager->flush();

            $this->addFlash('success', 'La ville a bien été modifiée !');

            return $this->redirectToRoute('cities_city_list');
        }

        return $this->render('city/edit.html.twig', [
            'city' => $city,
            'form' => $form->createView(),
        ]);
    }

    // Supprimer une ville
    #[Route('/{id}/delete', name: 'city_delete', methods: ['POST'])] // 👈 Changé : 'POST' uniquement pour la sécurité
    public function delete(City $city, EntityManagerInterface $entityManager): Response
    {
        // 1. On demande à l'EntityManager de préparer la suppression
        $entityManager->remove($city);

        // 2. On exécute la requête SQL DELETE en base de données
        $entityManager->flush();

        // 3. On affiche un message flash de confirmation
        $this->addFlash('success', 'La ville a bien été supprimée !');

        // 4. On redirige vers la liste des villes
        return $this->redirectToRoute('cities_city_list');
    }
}
