<?php

namespace App\Entity;

use App\Enum\EventStatus;
use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de la sortie ne peut pas etre vide')]
    #[Assert\Length(
        max: 255,
        min: 2,
        minMessage: 'Le titre de la sortie ne doit pas avoir moins de 2 caractères',
        maxMessage: 'Le titre de la sortie ne peut pas excéder 255 caractères'
    )]
    private ?string $name = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La date de début est obligatoire')]
    #[Assert\GreaterThan(
        value: 'now',
        message: 'La date de début doit être dans le futur'
    )]
    private ?\DateTime $startTime = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La durée est obligatoire')]
    #[Assert\Positive(message: 'La durée doit être un nombre strictement positif')]
    private ?int $duration = null;

    #[ORM\Column]
    private ?\DateTime $endTime = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La date limite d'inscription est obligatoire")]
    #[Assert\LessThan(
        propertyPath: 'startTime',
        message: "La date limite d'inscription doit être avant le début de la sortie"
    )]
    private ?\DateTime $registrationDeadline = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le nombre maximum de participants est obligatoire')]
    #[Assert\Positive(message: 'Le nombre de participants doit être supérieur à 0')]
    private ?int $maxParticipants = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $eventInfo = null;

    #[ORM\Column(length: 50, enumType: EventStatus::class)]
    private ?EventStatus $status = EventStatus::CREATED;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le campus est obligatoire')]
    private ?Campus $campus = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'partakenEvents')]
    private Collection $users;

    #[ORM\ManyToOne(inversedBy: 'createdEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $organizer = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le lieu est obligatoire')]
    private ?Location $location = null;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getStartTime(): ?\DateTime
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTime $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getRegistrationDeadline(): ?\DateTime
    {
        return $this->registrationDeadline;
    }

    public function setRegistrationDeadline(\DateTime $registrationDeadline): static
    {
        $this->registrationDeadline = $registrationDeadline;

        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;

        return $this;
    }

    public function getEventInfo(): ?string
    {
        return $this->eventInfo;
    }

    public function setEventInfo(?string $eventInfo): static
    {
        $this->eventInfo = $eventInfo;

        return $this;
    }

    public function getCampus(): ?Campus
    {
        return $this->campus;
    }

    public function setCampus(?Campus $campus): static
    {
        $this->campus = $campus;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        $this->users->removeElement($user);

        return $this;
    }

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }

    public function setOrganizer(?User $organizer): static
    {
        $this->organizer = $organizer;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }

    //    Automatic end-time calculator
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateEndTime(): void
    {
        if (null !== $this->startTime && null !== $this->duration) {
            $endtime = clone $this->startTime;
            $endtime->modify('+'.$this->duration.' minutes');
            $this->endTime = $endtime;
        }
    }

    //    Getter of the newly created property
    public function getEndTime(): ?\DateTime
    {
        return $this->endTime;
    }

    //    "Virtual" Status Getter
    public function getStatus(): ?EventStatus
    {
        $now = new \DateTime();

        // 1. Si l'événement est annulé, il reste annulé.
        if (EventStatus::CANCELED === $this->status) {
            return $this->status;
        }

        // Si on manque de données temporelles, on renvoie le statut brut (ex: CREATED)
        if (!$this->startTime || !$this->endTime || !$this->registrationDeadline) {
            return $this->status;
        }

        // 2. Calculs dynamiques prioritaires (le temps passe pour tout le monde)
        if ($now > $this->endTime) {
            return EventStatus::PAST;
        }

        if ($now >= $this->startTime && $now <= $this->endTime) {
            return EventStatus::IN_PROGRESS;
        }

        // 3. Si l'événement est publié (OPEN) ou CLOS en BDD, on gère les places et la deadline
        if (in_array($this->status, [EventStatus::OPEN, EventStatus::CLOSED])) {
            $isPastDeadline = $now > $this->registrationDeadline;
            $isFull = $this->users->count() >= $this->maxParticipants;

            if ($isPastDeadline || $isFull) {
                return EventStatus::CLOSED;
            }

            return EventStatus::OPEN;
        }

        // 4. Par défaut, retourne le statut brut de la BDD (ex: CREATED)
        return $this->status;
    }

    // Status Setter
    public function setStatus(?EventStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isPublished(): bool
    {
        return EventStatus::OPEN === $this->getStatus();
    }

    public function isRegistrationOpen(): bool
    {
        return (new \DateTime()) <= $this->registrationDeadline;
    }

    public function hasFreeSlots(): bool
    {
        return $this->users->count() < $this->maxParticipants;
    }

    public function isUserRegistered(User $user): bool
    {
        return $this->users->contains($user);
    }

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }
}
