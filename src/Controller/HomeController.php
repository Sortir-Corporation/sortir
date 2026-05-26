<?php

namespace App\Controller;

use App\Repository\CampusRepository;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        EventRepository $eventRepository,
        CampusRepository $campusRepository,
        Request $request,
    ): Response {
        $user = $this->getUser();
        $campuses = $campusRepository->findAll();

        $campusId = $request->query->get('campus');
        $search = $request->query->get('search', '');
        $dateStart = $request->query->get('date_start');
        $dateEnd = $request->query->get('date_end');
        $isOrganizer = $request->query->getBoolean('is_organizer');
        $isRegistered = $request->query->getBoolean('is_registered');
        $showPast = $request->query->getBoolean('show_past');

        $events = $eventRepository->findForHome(
            $user,
            $campusId ? (int) $campusId : null,
            $search,
            $dateStart ? new \DateTime($dateStart) : null,
            $dateEnd ? new \DateTime($dateEnd) : null,
            $isOrganizer,
            $isRegistered,
            $showPast,
        );

        return $this->render('home/home.html.twig', [
            'events' => $events,
            'campuses' => $campuses,
        ]);
    }
}
