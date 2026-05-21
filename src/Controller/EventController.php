<?php

namespace App\Controller;

use App\Entity\Event;
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
//    #[IsGranted('ROLE_USER')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $event = new Event();
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
}
