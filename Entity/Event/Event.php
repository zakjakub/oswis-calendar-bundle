<?php
/**
 * @noinspection MethodShouldBeFinalInspection
 * @noinspection RedundantDocCommentTagInspection
 * @noinspection PhpUnused
 */

namespace Zakjakub\OswisCalendarBundle\Entity\Event;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Zakjakub\OswisAddressBookBundle\Entity\Place;
use Zakjakub\OswisCalendarBundle\Entity\EventParticipant\EventParticipantFlag;
use Zakjakub\OswisCalendarBundle\Entity\EventParticipant\EventParticipantFlagInEventConnection;
use Zakjakub\OswisCalendarBundle\Entity\EventParticipant\EventParticipantType;
use Zakjakub\OswisCalendarBundle\Entity\EventParticipant\EventParticipantTypeInEventConnection;
use Zakjakub\OswisCalendarBundle\Entity\MediaObject\EventImage;
use Zakjakub\OswisCoreBundle\Entity\Nameable;
use Zakjakub\OswisCoreBundle\Filter\SearchAnnotation as Searchable;
use Zakjakub\OswisCoreBundle\Traits\Entity\BankAccountTrait;
use Zakjakub\OswisCoreBundle\Traits\Entity\BasicEntityTrait;
use Zakjakub\OswisCoreBundle\Traits\Entity\ColorTrait;
use Zakjakub\OswisCoreBundle\Traits\Entity\DateRangeTrait;
use Zakjakub\OswisCoreBundle\Traits\Entity\DeletedTrait;
use Zakjakub\OswisCoreBundle\Traits\Entity\EntityPublicTrait;
use Zakjakub\OswisCoreBundle\Traits\Entity\NameableBasicTrait;
use Zakjakub\OswisCoreBundle\Utils\DateTimeUtils;
use function assert;

/**
 * @Doctrine\ORM\Mapping\Entity(repositoryClass="Zakjakub\OswisCalendarBundle\Repository\EventRepository")
 * @Doctrine\ORM\Mapping\Table(name="calendar_event")
 * @ApiResource(
 *   attributes={
 *     "filters"={"search"},
 *     "access_control"="is_granted('ROLE_MANAGER')"
 *   },
 *   collectionOperations={
 *     "get"={
 *       "access_control"="is_granted('ROLE_MANAGER')",
 *       "normalization_context"={"groups"={"calendar_events_get"}, "enable_max_depth"=true},
 *     },
 *     "post"={
 *       "access_control"="is_granted('ROLE_MANAGER')",
 *       "denormalization_context"={"groups"={"calendar_events_post"}, "enable_max_depth"=true}
 *     }
 *   },
 *   itemOperations={
 *     "get"={
 *       "access_control"="is_granted('ROLE_MANAGER')",
 *       "normalization_context"={"groups"={"calendar_event_get"}, "enable_max_depth"=true},
 *     },
 *     "put"={
 *       "access_control"="is_granted('ROLE_MANAGER')",
 *       "denormalization_context"={"groups"={"calendar_event_put"}, "enable_max_depth"=true}
 *     },
 *     "delete"={
 *       "access_control"="is_granted('ROLE_MANAGER')",
 *       "denormalization_context"={"groups"={"calendar_event_delete"}, "enable_max_depth"=true}
 *     }
 *   }
 * )
 * @ApiFilter(OrderFilter::class)
 * @Searchable({
 *     "id",
 *     "name",
 *     "description",
 *     "note",
 *     "shortName",
 *     "slug"
 * })
 * @Doctrine\ORM\Mapping\Cache(usage="NONSTRICT_READ_WRITE", region="calendar_event")
 */
class Event
{
    use BasicEntityTrait;
    use NameableBasicTrait;
    use DateRangeTrait;
    use ColorTrait;
    use BankAccountTrait;
    use DeletedTrait;
    use EntityPublicTrait;

    /**
     * @Doctrine\ORM\Mapping\ManyToOne(targetEntity="Zakjakub\OswisAddressBookBundle\Entity\Place", fetch="EAGER")
     * @Doctrine\ORM\Mapping\JoinColumn(nullable=true)
     */
    protected ?Place $location = null;

    /**
     * @var Collection<EventFlagConnection> $eventFlagConnections
     * @Doctrine\ORM\Mapping\OneToMany(
     *     targetEntity="Zakjakub\OswisCalendarBundle\Entity\Event\EventFlagConnection",
     *     cascade={"all"},
     *     mappedBy="event",
     *     fetch="EAGER"
     * )
     * @MaxDepth(1)
     */
    protected ?Collection $eventFlagConnections = null;

    /**
     * Parent event (if this is not top level event).
     * @Doctrine\ORM\Mapping\ManyToOne(
     *     targetEntity="Zakjakub\OswisCalendarBundle\Entity\Event\Event",
     *     inversedBy="subEvents",
     *     fetch="EAGER"
     * )
     * @Doctrine\ORM\Mapping\JoinColumn(nullable=true)
     */
    protected ?Event $superEvent = null;

    /**
     * @var Collection<Event> $subEvents
     * @Doctrine\ORM\Mapping\OneToMany(targetEntity="Zakjakub\OswisCalendarBundle\Entity\Event\Event", mappedBy="superEvent", fetch="EAGER")
     */
    protected ?Collection $subEvents = null;

    /**
     * @var Collection<EventWebContent> $webContents
     * @Doctrine\ORM\Mapping\ManyToMany(targetEntity="Zakjakub\OswisCalendarBundle\Entity\Event\EventWebContent", cascade={"all"}, fetch="EAGER")
     * @Doctrine\ORM\Mapping\JoinTable(
     *     name="calendar_event_web_content_connection",
     *     joinColumns={@Doctrine\ORM\Mapping\JoinColumn(name="event_id", referencedColumnName="id")},
     *     inverseJoinColumns={@Doctrine\ORM\Mapping\JoinColumn(name="event_web_content_id", referencedColumnName="id", unique=true)}
     * )
     */
    protected ?Collection $webContents = null;

    /**
     * @var Collection<EventRegistrationRange> $registrationRanges
     * @Doctrine\ORM\Mapping\ManyToMany(
     *     targetEntity="Zakjakub\OswisCalendarBundle\Entity\Event\EventRegistrationRange",
     *     cascade={"all"},
     *     fetch="EAGER"
     * )
     * @Doctrine\ORM\Mapping\JoinTable(
     *     name="calendar_event_registration_range_connection",
     *     joinColumns={@Doctrine\ORM\Mapping\JoinColumn(name="event_id", referencedColumnName="id")},
     *     inverseJoinColumns={@Doctrine\ORM\Mapping\JoinColumn(name="event_registration_range_id", referencedColumnName="id", unique=true)}
     * )
     */
    protected ?Collection $registrationRanges = null;

    /**
     * @var Collection<EventParticipantTypeInEventConnection> $participantTypeInEventConnections
     * @Doctrine\ORM\Mapping\OneToMany(
     *     targetEntity="Zakjakub\OswisCalendarBundle\Entity\EventParticipant\EventParticipantTypeInEventConnection",
     *     cascade={"all"},
     *     mappedBy="event",
     *     fetch="EAGER"
     * )
     */
    protected ?Collection $participantTypeInEventConnections = null;

    /**
     * @var Collection<EventParticipantFlagInEventConnection> $participantFlagInEventConnections
     * @Doctrine\ORM\Mapping\OneToMany(
     *     targetEntity="Zakjakub\OswisCalendarBundle\Entity\EventParticipant\EventParticipantFlagInEventConnection",
     *     cascade={"all"},
     *     mappedBy="event",
     *     fetch="EAGER"
     * )
     */
    protected ?Collection $participantFlagInEventConnections = null;

    /**
     * @Doctrine\ORM\Mapping\OneToOne(targetEntity="Zakjakub\OswisCalendarBundle\Entity\MediaObject\EventImage", cascade={"all"}, fetch="EAGER")
     */
    protected ?EventImage $image = null;

    /**
     * @Doctrine\ORM\Mapping\ManyToOne(targetEntity="Zakjakub\OswisCalendarBundle\Entity\Event\EventType",fetch="EAGER")
     * @Doctrine\ORM\Mapping\JoinColumn(name="type_id", referencedColumnName="id")
     */
    private ?EventType $type = null;

    /**
     * @Doctrine\ORM\Mapping\ManyToOne(targetEntity="Zakjakub\OswisCalendarBundle\Entity\Event\EventSeries", inversedBy="events", fetch="EAGER")
     * @Doctrine\ORM\Mapping\JoinColumn(name="event_series_id", referencedColumnName="id")
     */
    private ?EventSeries $series = null;

    /**
     * Indicates if price is relative to parent event.
     * @Doctrine\ORM\Mapping\Column(type="boolean", nullable=true)
     */
    private ?bool $priceRelative = null;

    public function __construct(
        ?Nameable $nameable = null,
        ?Event $superEvent = null,
        ?Place $location = null,
        ?EventType $type = null,
        ?DateTime $startDateTime = null,
        ?DateTime $endDateTime = null,
        ?EventSeries $series = null,
        ?bool $priceRelative = null,
        ?string $color = null,
        ?string $bankAccountNumber = null,
        ?string $bankAccountBank = null
    ) {
        $this->subEvents = new ArrayCollection();
        $this->registrationRanges = new ArrayCollection();
        $this->participantTypeInEventConnections = new ArrayCollection();
        $this->participantFlagInEventConnections = new ArrayCollection();
        $this->setType($type);
        $this->setSuperEvent($superEvent);
        $this->setSeries($series);
        $this->setPriceRelative($priceRelative);
        $this->setFieldsFromNameable($nameable);
        $this->setLocation($location);
        $this->setStartDateTime($startDateTime);
        $this->setEndDateTime($endDateTime);
        $this->setColor($color);
        $this->setBankAccountNumber($bankAccountNumber);
        $this->setBankAccountBank($bankAccountBank);
    }

    public function setPriceRelative(?bool $priceRelative): void
    {
        $this->priceRelative = $priceRelative;
    }

    public function getImage(): ?EventImage
    {
        return $this->image;
    }

    public function setImage(?EventImage $image): void
    {
        $this->image = $image;
    }

    public function destroyRevisions(): void
    {
    }

    public function addRegistrationRange(?EventRegistrationRange $eventRegistrationRange): void
    {
        if (null !== $eventRegistrationRange && !$this->registrationRanges->contains($eventRegistrationRange)) {
            $this->registrationRanges->add($eventRegistrationRange);
        }
    }

    public function addEventFlagConnection(?EventFlagConnection $eventContactFlagConnection): void
    {
        if (null !== $eventContactFlagConnection && !$this->eventFlagConnections->contains($eventContactFlagConnection)) {
            $this->eventFlagConnections->add($eventContactFlagConnection);
            $eventContactFlagConnection->setEvent($this);
        }
    }

    public function getParticipantTypes(?string $type = null): Collection
    {
        return $this->getParticipantTypeInEventConnections()->map(
            fn(EventParticipantTypeInEventConnection $conn) => $conn->getEventParticipantType()
        )->filter(fn(EventParticipantType $t) => empty($type) || $type === $t->getType());
    }

    public function getParticipantTypeInEventConnections(): Collection
    {
        return $this->participantTypeInEventConnections ?? new ArrayCollection();
    }

    public function isRoot(): bool
    {
        return $this->getSuperEvent() ? false : true;
    }

    public function getSuperEvent(): ?Event
    {
        return $this->superEvent;
    }

    public function setSuperEvent(?Event $event): void
    {
        if ($this->superEvent && $event !== $this->superEvent) {
            $this->superEvent->removeSubEvent($this);
        }
        $this->superEvent = $event;
        if ($this->superEvent) {
            $this->superEvent->addSubEvent($this);
        }
    }

    public function addSubEvent(?Event $event): void
    {
        if (null !== $event && !$this->subEvents->contains($event)) {
            $this->subEvents->add($event);
            $event->setSuperEvent($this);
        }
    }

    public function removeSubEvent(?Event $event): void
    {
        if (null !== $event && $this->subEvents->removeElement($event)) {
            $event->setSuperEvent(null);
        }
    }

    public function getCapacity(?EventParticipantType $participantType = null, ?DateTime $dateTime = null): ?int
    {
        $capacity = null;
        foreach ($this->getRegistrationRanges($participantType, $dateTime) as $range) {
            assert($range instanceof EventRegistrationRange);
            if (null !== $range->getCapacity()) {
                $capacity += $range->getCapacity();
            }
        }

        return $capacity;
    }

    /**
     * @param EventParticipantType|null $participantType
     * @param DateTime|null             $dateTime
     *
     * @return Collection<EventRegistrationRange>
     */
    public function getRegistrationRanges(?EventParticipantType $participantType = null, ?DateTime $dateTime = null): Collection
    {
        if (null !== $participantType || null !== $dateTime) {
            return $this->getRegistrationRanges()->filter(
                fn(EventRegistrationRange $range) => $range->isApplicableByType($participantType, $dateTime)
            );
        }

        return $this->registrationRanges ?? new ArrayCollection();
    }

    public function getMaxCapacity(?EventParticipantType $participantType = null, ?DateTime $dateTime = null): ?int
    {
        $capacity = null;
        foreach ($this->getRegistrationRanges($participantType, $dateTime) as $range) {
            assert($range instanceof EventRegistrationRange);
            if (null !== $range->getCapacity() || null !== $range->getCapacityOverflowLimit()) {
                $capacity += $range->getCapacity();
                $capacity += $range->getCapacityOverflowLimit();
            }
        }

        return $capacity;
    }

    public function addParticipantTypeInEventConnection(?EventParticipantTypeInEventConnection $participantTypeInEventConnection): void
    {
        if ($participantTypeInEventConnection && !$this->participantTypeInEventConnections->contains($participantTypeInEventConnection)) {
            $this->participantTypeInEventConnections->add($participantTypeInEventConnection);
            $participantTypeInEventConnection->setEvent($this);
        }
    }

    public function removeParticipantTypeInEventConnection(?EventParticipantTypeInEventConnection $participantTypeInEventConnection): void
    {
        if ($participantTypeInEventConnection && $this->participantTypeInEventConnections->removeElement($participantTypeInEventConnection)) {
            $participantTypeInEventConnection->setEvent(null);
        }
    }

    public function addParticipantFlagInEventConnection(?EventParticipantFlagInEventConnection $participantFlagInEventConnection): void
    {
        if ($participantFlagInEventConnection && !$this->participantFlagInEventConnections->contains($participantFlagInEventConnection)) {
            $this->participantFlagInEventConnections->add($participantFlagInEventConnection);
            $participantFlagInEventConnection->setEvent($this);
        }
    }

    public function removeParticipantFlagInEventConnection(?EventParticipantFlagInEventConnection $participantFlagInEventConnection): void
    {
        if ($participantFlagInEventConnection && $this->participantFlagInEventConnections->removeElement($participantFlagInEventConnection)) {
            $participantFlagInEventConnection->setEvent(null);
        }
    }

    public function removeRegistrationRange(?EventRegistrationRange $eventRegistrationRange): void
    {
        if (null !== $eventRegistrationRange) {
            $this->registrationRanges->removeElement($eventRegistrationRange);
        }
    }

    public function getPrice(EventParticipantType $participantType, ?DateTime $dateTime = null): ?int
    {
        $total = null;
        foreach ($this->getRegistrationRanges($participantType, $dateTime) as $range) {
            assert($range instanceof EventRegistrationRange);
            if (null !== $range->getNumericValue()) {
                $total += $range->getNumericValue();
            }
            if ($range->isRelative() && null !== $this->getSuperEvent()) {
                $total += $this->getSuperEvent()->getPrice($participantType);
            }
        }

        return null !== $total && $total <= 0 ? 0 : $total;
    }

    public function getDeposit(EventParticipantType $eventParticipantType): ?int
    {
        if ($this->getDepositOfEvent($eventParticipantType) !== null) {
            return $this->getDepositOfEvent($eventParticipantType);
        }

        return $this->isPriceRelative() && $this->getSuperEvent() ? $this->getSuperEvent()->getDeposit($eventParticipantType) : 0;
    }

    public function getDepositOfEvent(EventParticipantType $participantType, ?DateTime $dateTime = null): ?int
    {
        $total = null;
        foreach ($this->getRegistrationRanges($participantType, $dateTime) as $range) {
            assert($range instanceof EventRegistrationRange);
            if (null !== $range->getDepositValue()) {
                $total += $range->getDepositValue();
            }
            if ($range->isRelative() && null !== $this->getSuperEvent()) {
                $total += $this->getSuperEvent()->getDeposit($participantType);
            }
        }

        return null !== $total && $total <= 0 ? 0 : $total;
    }

    public function isPriceRelative(): bool
    {
        return $this->priceRelative ?? false;
    }

    public function addWebContent(?EventWebContent $eventWebContent): void
    {
        if (null !== $eventWebContent && !$this->webContents->contains($eventWebContent)) {
            $this->removeWebContent($this->getWebContent($eventWebContent->getType()));
            $this->webContents->add($eventWebContent);
        }
    }

    public function removeWebContent(?EventWebContent $eventWebContent): void
    {
        if (null !== $eventWebContent) {
            $this->webContents->removeElement($eventWebContent);
        }
    }

    public function getWebContent(?string $type = 'html'): ?EventWebContent
    {
        return $this->getWebContents($type)->first();
    }

    public function getWebContents(?string $type = null): Collection
    {
        if (null !== $type) {
            $this->getWebContents()->filter(fn(EventWebContent $webContent) => $type === $webContent->getType());
        }

        return $this->webContents ?? new ArrayCollection();
    }

    public function getLocation(?bool $recursive = false): ?Place
    {
        return $this->location ?? ($recursive && $this->getSuperEvent() ? $this->getSuperEvent()->getLocation() : null) ?? null;
    }

    public function setLocation(?Place $event): void
    {
        $this->location = $event;
    }

    public function getStartDateTimeRecursive(): ?DateTime
    {
        $maxDateTime = new DateTime(DateTimeUtils::MAX_DATE_TIME_STRING);
        $startDateTime = $this->getStartDateTime() ?? $maxDateTime;
        foreach ($this->getSubEvents() as $subEvent) {
            assert($subEvent instanceof self);
            $dateTime = $subEvent->getStartDateTimeRecursive();
            if ($dateTime && $dateTime < $startDateTime) {
                $startDateTime = $dateTime;
            }
        }

        return $startDateTime === $maxDateTime ? null : $startDateTime;
    }

    public function getSubEvents(): Collection
    {
        return $this->subEvents ?? new ArrayCollection();
    }

    public function getEndDateTimeRecursive(): ?DateTime
    {
        $minDateTime = new DateTime(DateTimeUtils::MIN_DATE_TIME_STRING);
        $endDateTime = $this->getEndDateTime() ?? $minDateTime;
        foreach ($this->getSubEvents() as $subEvent) {
            assert($subEvent instanceof self);
            $dateTime = $subEvent->getEndDateTimeRecursive();
            if ($dateTime && $dateTime > $endDateTime) {
                $endDateTime = $dateTime;
            }
        }

        return $endDateTime === $minDateTime ? null : $endDateTime;
    }

    public function getAllowedFlagsAggregatedByType(?EventParticipantType $eventParticipantType = null): array
    {
        $flags = [];
        foreach ($this->getParticipantFlagInEventConnections($eventParticipantType) as $flagInEventConnection) {
            assert($flagInEventConnection instanceof EventParticipantFlagInEventConnection);
            $flag = $flagInEventConnection->getEventParticipantFlag();
            if ($flag) {
                $flagTypeId = $flag->getEventParticipantFlagType() ? $flag->getEventParticipantFlagType()->getSlug() : '';
                $flags[$flagTypeId]['flagType'] = $flag->getEventParticipantFlagType();
                $flags[$flagTypeId]['flags'][] = $flag;
            }
        }

        return $flags;
    }

    public function getParticipantFlagInEventConnections(
        EventParticipantType $participantType = null,
        ?EventParticipantFlag $participantFlag = null
    ): Collection {
        $out = $this->participantFlagInEventConnections ?? new ArrayCollection();
        if (null !== $participantType) {
            $out = $out->filter(
                fn(EventParticipantFlagInEventConnection $c) => $c->getEventParticipantType() && $participantType->getId() === $c->getEventParticipantType()->getId()
            );
        }
        if (null !== $participantFlag) {
            $out = $out->filter(
                fn(EventParticipantFlagInEventConnection $c) => $c->getEventParticipantFlag() && $participantFlag->getId() === $c->getEventParticipantFlag()->getId()
            );
        }

        return $out;
    }

    /**
     * True if registrations for specified participant type (or any if not specified) is allowed in some datetime (or now if not specified).
     *
     * @param EventParticipantType $participantType
     * @param DateTime|null        $dateTime
     *
     * @return bool
     */
    public function isRegistrationsAllowed(?EventParticipantType $participantType = null, ?DateTime $dateTime = null): bool
    {
        return $this->getRegistrationRanges($participantType, $dateTime)->count() > 0;
    }

    public function getRegistrationRangesByTypeOfType(?string $participantType = null, ?DateTime $dateTime = null): Collection
    {
        if (null !== $participantType || null !== $dateTime) {
            return $this->getRegistrationRanges(null, $dateTime)->filter(
                fn(EventRegistrationRange $range) => $range->isApplicableByTypeOfType($participantType)
            );
        }

        return $this->getRegistrationRanges();
    }

    public function __toString(): string
    {
        $range = $this->getRangeAsText();

        return $this->getName().($range ? (' ('.$range.')') : null);
    }

    public function getAllowedParticipantFlagAmount(?EventParticipantFlag $participantFlag, ?EventParticipantType $participantType): int
    {
        $allowedAmount = 0;
        foreach ($this->getParticipantFlagInEventConnections($participantType, $participantFlag) as $flagInEventConnection) {
            assert($flagInEventConnection instanceof EventParticipantFlagInEventConnection);
            $allowedAmount += $flagInEventConnection->getActive() ? $flagInEventConnection->getMaxAmountInEvent() : 0;
        }

        return $allowedAmount;
    }

    public function getEventFlagConnections(): ?Collection
    {
        return $this->eventFlagConnections ?? new ArrayCollection();
    }

    public function removeEventFlagConnection(?EventFlagConnection $eventContactFlagConnection): void
    {
        if (null !== $eventContactFlagConnection && $this->eventFlagConnections->removeElement($eventContactFlagConnection)) {
            $eventContactFlagConnection->setEvent(null);
        }
    }

    public function getGeneratedSlug(): string /// TODO: Used somewhere?
    {
        if ($this->isBatchOrYear() && $this->getStartYear()) {
            return $this->getStartYear().($this->isBatch() ? '-'.$this->getSeqId() : null);
        }

        return (string)$this->getId();
    }

    public function isBatchOrYear(): bool
    {
        return $this->isYear() || $this->isBatch();
    }

    public function isYear(): bool
    {
        return null !== $this->getType() && EventType::YEAR_OF_EVENT === $this->getType()->getType();
    }

    public function getType(): ?EventType
    {
        return $this->type;
    }

    public function setType(?EventType $type): void
    {
        $this->type = $type;
    }

    public function isBatch(): bool
    {
        return $this->getType() && EventType::BATCH_OF_EVENT === $this->getType()->getType();
    }

    public function getStartYear(): ?int
    {
        return (int)$this->getStartByFormat(DateTimeUtils::DATE_TIME_YEARS);
    }

    public function getSeqId(): ?int
    {
        return $this->getSeries() ? $this->getSeries()->getSeqId($this) : null;
    }

    public function getSeries(): ?EventSeries
    {
        return $this->series;
    }

    public function setSeries(?EventSeries $series): void
    {
        if (null !== $this->series && $series !== $this->series) {
            $this->series->removeEvent($this);
        }
        $this->series = $series;
        if (null !== $series && $this->series !== $series) {
            $series->addEvent($this);
        }
    }

    public function isSuperEvent(?Event $event, ?bool $recursive = true): bool
    {
        return in_array($event, $recursive ? $this->getSuperEvents() : [$this->getSuperEvent()], true);
    }

    public function getSuperEvents(): array
    {
        return null === $this->getSuperEvent() ? [...$this->getSuperEvents(), $this->getSuperEvent()] : [$this];
    }

    public function isSuperEventRequired(?EventParticipantType $participantType, ?DateTime $dateTime = null): bool
    {
        return $this->getRegistrationRanges($participantType, $dateTime)->exists(
            fn(EventRegistrationRange $price) => $price->isSuperEventRequired()
        );
    }
}
