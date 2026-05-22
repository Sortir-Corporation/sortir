<?php

namespace App\Controller;

use App\Entity\Event;
use App\Enum\EventStatus;
use App\Form\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/events', name: 'events_')]
final class EventController extends AbstractController
{
    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $event = new Event();

        $user = $this->getUser();

        $event->setOrganizer($user);
        $event->setCampus($user->getCampus());

        $eventForm = $this->createForm(EventType::class, $event, [
            'action' => $this->generateUrl('events_create'),
            'method' => 'POST',
        ]);
        $eventForm->handleRequest($request);
        if ($eventForm->isSubmitted() && $eventForm->isValid()) {
            try {
                $entityManager->persist($event);
                $entityManager->flush();
                $this->addFlash('success', 'Event created');

                return $this->redirectToRoute('events');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred: '.$e->getMessage());
            }
        }

        return $this->render('event/create.html.twig', [
            'eventForm' => $eventForm,
        ]);
    }

    #[Route('/{id}/register', name: 'register', methods: ['GET', 'POST'])]
    public function register(
        Event $event,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$event->isPublished()) {
            $this->addFlash('error', 'Event is not published');
            //            return $this->redirectToRoute('events_details', ['id'=>$event->getId()]);
        }

        if (!$event->isRegistrationOpen()) {
            $this->addFlash('error', 'Registration deadline has passed');
            //            return $this->redirectToRoute('events_details', ['id'=>$event->getId()]);
        }

        if (!$event->hasFreeSlots()) {
            $this->addFlash('error', 'This event is full');
            //            return $this->redirectToRoute('events_details', ['id'=>$event->getId()]);
        }

        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to register');

            return $this->redirectToRoute('app_login');
        }

        if ($event->getUsers()->contains($user)) {
            $this->addFlash('info', 'Vous êtes déjà inscrit.');

            return $this->redirectToRoute('events_details', ['id' => $event->getId()]);
        }

        $event->addUser($user);
        $entityManager->flush();

        $this->addFlash('success', 'Inscription réussie !');

        return $this->redirectToRoute('events_details', ['id' => $event->getId()]);
    }

    #[Route('/{id}', name: 'details', methods: ['GET'])]
    public function details(Event $event): Response
    {
        return $this->render('event/details.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}/unregister', name: 'unRegister', methods: ['GET', 'POST'])]
    public function unregister(Event $event, EntityManagerInterface $em): Response
    {
        // 1. On récupère l'utilisateur actuellement connecté
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'You must be logged in to unregister.');

            return $this->redirectToRoute('app_login');
        }

        // 2. On vérifie d'ABORD si l'événement est encore ouvert aux modifications
        if (EventStatus::OPEN !== $event->getStatus()) {
            $this->addFlash('error', 'You cannot unregister because this event is no longer open.');

            return $this->redirectToRoute('events_details', ['id' => $event->getId()]);
        }

        // 3. Si c'est ouvert, on vérifie s'il est inscrit pour pouvoir le retirer
        if ($event->getUsers()->contains($user)) {
            $event->removeUser($user);
            $em->flush(); // Sauvegarde en BDD

            $this->addFlash('success', 'You have been successfully unregistered from this event.');
        } else {
            $this->addFlash('warning', 'You were not registered for this event.');
        }

        return $this->redirectToRoute('events_details', ['id' => $event->getId()]);
    }
}
