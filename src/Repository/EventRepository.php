<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use App\Enum\EventStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @return Event[]
     */
    public function findForHome(
        User $user,
        ?int $campusId,
        string $search,
        ?\DateTime $dateStart,
        ?\DateTime $dateEnd,
        bool $isOrganizer,
        bool $isRegistered,
        bool $showPast,
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->join('e.campus', 'c')
            ->join('e.organizer', 'o')
            ->join('e.location', 'l')
            ->addSelect('c', 'o', 'l')
            ->orderBy('e.startTime', 'ASC');

        // Sorties annulées visibles uniquement par l'organisateur
        $qb->andWhere('e.status != :cancelled OR e.organizer = :user')
            ->setParameter('cancelled', EventStatus::CANCELED)
            ->setParameter('user', $user);

        if (!$showPast) {
            $qb->andWhere('e.startTime >= :today')
                ->setParameter('today', new \DateTime('today'));
        }

        if ($campusId) {
            $qb->andWhere('c.id = :campusId')
                ->setParameter('campusId', $campusId);
        }

        if ('' !== $search) {
            $qb->andWhere('e.name LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($dateStart) {
            $qb->andWhere('e.startTime >= :dateStart')
                ->setParameter('dateStart', $dateStart);
        }

        if ($dateEnd) {
            $qb->andWhere('e.startTime <= :dateEnd')
                ->setParameter('dateEnd', $dateEnd);
        }

        if ($isOrganizer) {
            $qb->andWhere('e.organizer = :user');
        }

        if ($isRegistered) {
            $qb->join('e.users', 'u')
                ->andWhere('u = :user');
        }

        return $qb->getQuery()->getResult();
    }

    public function haveCommonEvent(User $userA, User $userB): bool
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('COUNT(e.id)')
            ->innerJoin('e.users', 'u1', 'WITH', 'u1.id = :userA')
            ->innerJoin('e.users', 'u2', 'WITH', 'u2.id = :userB')
            ->setParameters([
                'userA' => $userA->getId(),
                'userB' => $userB->getId()
            ]);

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }


}
