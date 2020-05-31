<?php
/**
 * @noinspection MethodShouldBeFinalInspection
 * @noinspection RedundantDocCommentTagInspection
 * @noinspection PhpUnused
 */

namespace OswisOrg\OswisCalendarBundle\Entity\Event;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use OswisOrg\OswisAddressBookBundle\Entity\Place;
use OswisOrg\OswisCalendarBundle\Entity\EventParticipant\ParticipantFlag;
use OswisOrg\OswisCalendarBundle\Entity\EventParticipant\ParticipantFlagRange;
use OswisOrg\OswisCalendarBundle\Entity\EventParticipant\ParticipantType;
use OswisOrg\OswisCalendarBundle\Entity\EventParticipant\ParticipantTypeInEventConnection;
use OswisOrg\OswisCalendarBundle\Entity\MediaObjects\EventImage;
use OswisOrg\OswisCoreBundle\Entity\NonPersistent\DateTimeRange;
use OswisOrg\OswisCoreBundle\Entity\NonPersistent\Nameable;
use OswisOrg\OswisCoreBundle\Entity\NonPersistent\Publicity;
use OswisOrg\OswisCoreBundle\Filter\SearchAnnotation as Searchable;
use OswisOrg\OswisCoreBundle\Interfaces\Common\NameableInterface;
use OswisOrg\OswisCoreBundle\Traits\Common\ColorTrait;
use OswisOrg\OswisCoreBundle\Traits\Common\DateRangeTrait;
use OswisOrg\OswisCoreBundle\Traits\Common\DeletedTrait;
use OswisOrg\OswisCoreBundle\Traits\Common\EntityPublicTrait;
use OswisOrg\OswisCoreBundle\Traits\Common\NameableTrait;
use OswisOrg\OswisCoreBundle\Traits\Payment\BankAccountTrait;
use OswisOrg\OswisCoreBundle\Utils\DateTimeUtils;
use function assert;

/**
 * @Doctrine\ORM\Mapping\Entity(repositoryClass="OswisOrg\OswisCalendarBundle\Repository\EventRepository")
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
class Event implements NameableInterface
{
    use NameableTrait;
    use DateRangeTrait;
    use ColorTrait;
    use BankAccountTrait;
    use DeletedTrait;
    use EntityPublicTrait;

    /**
     * @Doctrine\ORM\Mapping\ManyToOne(targetEntity="OswisOrg\OswisAddressBookBundle\Entity\Place", fetch="EAGER")
     * @Doctrine\ORM\Mapping\JoinColumn(nullable=true)
     */
    protected ?Place $location = null;

    /**
     * @var Collection<EventFlagConnection> $flagConnections
     * @Doctrine\ORM\Mapping\OneToMany(
     *     targetEntity="OswisOrg\OswisCalendarBundle\Entity\Event\EventFlagConnection",
     *     cascade={"all"},
     *     mappedBy="event",
     *     fetch="EAGER"
     * )
     */
    protected ?Collection $flagConnections = null;

    /**
     * Parent event (if this is not top level event).
     * @Doctrine\ORM\Mapping\ManyToOne(
     *     targetEntity="OswisOrg\OswisCalendarBundle\Entity\Event\Event",
     *     inversedBy="subEvents",
     *     fetch="EAGER"
     * )
     * @Doctrine\ORM\Mapping\JoinColumn(nullable=true)
     */
    protected ?Event $superEvent = null;

    /**
     * @var Collection<Event> $subEvents
     * @Doctrine\ORM\Mapping\OneToMany(targetEntity="OswisOrg\OswisCalendarBundle\Entity\Event\Event", mappedBy="superEvent")
     */
    protected ?Collection $subEvents = null;

    /**
     * @var Collection<EventWebContent> $webContents
     * @Doctrine\ORM\Mapping\ManyToMany(targetEntity="OswisOrg\OswisCalendarBundle\Entity\Event\EventWebContent", cascade={"all"})
     * @Doctrine\ORM\Mapping\JoinTable(
     *     name="calendar_event_web_content_connection",
     *     joinColumns={@Doctrine\ORM\Mapping\JoinColumn(name="event_id", referencedColumnName="id")},
     *     inverseJoinColumns={@Doctrine\ORM\Mapping\JoinColumn(name="event_web_content_id", referencedColumnName="id", unique=true)}
     * )
     */
    protected ?Collection $webContents = null;

    /**
     * @var Collection<RegistrationsRange> $registrationRanges
     * @Doctrine\ORM\Mapping\ManyToMany(
     *     targetEntity="OswisOrg\OswisCalendarBundle\Entity\Event\RegistrationsRange",
     *     cascade={"all"}
     * )
     * @Doctrine\ORM\Mapping\JoinTable(
     *     name="calendar_event_registration_range_connection",
     *     joinColumns={@Doctrine\ORM\Mapping\JoinColumn(name="event_id", referencedColumnName="id")},
     *     inverseJoinColumns={@Doctrine\ORM\Mapping\JoinColumn(name="event_registration_range_id", referencedColumnName="id", unique=true)}
     * )
     */
    protected ?Collection $registrationRanges = null;

    /**
     * @var Collection<ParticipantFlagRange> $participantFlagRanges
     * @Doctrine\ORM\Mapping\OneToMany(
     *     targetEntity="OswisOrg\OswisCalendarBundle\Entity\EventParticipant\ParticipantFlagRange",
     *     cascade={"all"},
     *     mappedBy="event",
     *     fetch="EAGER"
     * )
     */
    protected ?Collection $participantFlagRanges = null;

    /**
     * @Doctrine\ORM\Mapping\OneToOne(targetEntity="OswisOrg\OswisCalendarBundle\Entity\MediaObjects\EventImage", cascade={"all"}, fetch="EAGER")
     */
    protected ?EventImage $image = null;

    /**
     * @Doctrine\ORM\Mapping\ManyToOne(targetEntity="OswisOrg\OswisCalendarBundle\Entity\Event\EventType", fetch="EAGER")
     * @Doctrine\ORM\Mapping\JoinColumn(name="type_id", referencedColumnName="id")
     */
    private ?EventType $type = null;

    /**
     * @Doctrine\ORM\Mapping\ManyToOne(targetEntity="OswisOrg\OswisCalendarBundle\Entity\Event\EventSeries", inversedBy="events", fetch="EAGER")
     * @Doctrine\ORM\Mapping\JoinColumn(name="event_series_id", referencedColumnName="id")
     */
    private ?EventSeries $series = null;

    public function __construct(
        ?Nameable $nameable = null,
        ?Event $superEvent = null,
        ?Place $location = null,
        ?EventType $type = null,
        ?DateTimeRange $dateTimeRange = null,
        ?EventSeries $series = null,
        ?string $color = null,
        ?Publicity $publicity = null
    ) {
        $this->subEvents = new ArrayCollection();
        $this->registrationRanges = new ArrayCollection();
        $this->participantFlagRanges = new ArrayCollection();
        $this->webContents = new ArrayCollection();
        $this->setType($type);
        $this->setSuperEvent($superEvent);
        $this->setSeries($series);
        $this->setFieldsFromNameable($nameable);
        $this->setLocation($location);
        $this->setDateTimeRange($dateTimeRange);
        $this->setColor($color);
        $this->setFieldsFromPublicity($publicity);
    }

    public function setBankAccount(?string $number, ?string $bank): void
    {
        $this->setBankAccountNumber($number);
        $this->setBankAccountBank($bank);
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

    public function addRegistrationRange(?RegistrationsRange $registrationRange): void
    {
        if (null !== $registrationRange && !$this->getRegistrationRanges()->contains($registrationRange)) {
            $this->getRegistrationRanges()->add($registrationRange);
        }
    }

    public function getRegistrationRanges(
        ?ParticipantType $participantType = null,
        ?DateTime $dateTime = null,
        ?bool $recursive = false,
        ?bool $onlyPublic = false
    ): Collection {
        $ranges = $this->registrationRanges ?? new ArrayCollection();
        if (null !== $participantType || null !== $dateTime) {
            $ranges = $this->getRegistrationRanges()->filter(fn(RegistrationsRange $range) => $range->isApplicableByType($participantType, $dateTime));
        }
        if (true === $onlyPublic) {
            $ranges = $ranges->filter(fn(RegistrationsRange $range) => $range->isPublicOnWeb());
        }
        if (true === $recursive) {
            foreach ($this->getSubEvents() as $subEvent) {
                assert($subEvent instanceof self);
                $subEvent->getRegistrationRanges($participantType, $dateTime, $recursive)->map(
                    fn(RegistrationsRange $range) => $ranges->contains($range) ? null : $ranges->add($range)
                );
            }
        }

        return $this->registrationRanges ?? new ArrayCollection();
    }

    public function getSubEvents(): Collection
    {
        return $this->subEvents ?? new ArrayCollection();
    }

    public function addFlagConnection(?EventFlagConnection $flagConnection): void
    {
        if (null !== $flagConnection && !$this->getFlagConnections()->contains($flagConnection)) {
            $this->getFlagConnections()->add($flagConnection);
            $flagConnection->setEvent($this);
        }
    }

    public function getFlagConnections(): Collection
    {
        return $this->flagConnections ?? new ArrayCollection();
    }

    public function getParticipantTypes(?string $type = null, bool $onlyPublic = false): Collection
    {
        $types = $this->getRegistrationRangesByTypeString($type, null, false, $onlyPublic)->map(
            fn(RegistrationsRange $range) => $range->getParticipantType()
        );
        if (!empty($type)) {
            $types = $types->filter(fn(ParticipantType $t) => empty($type) || $t->getType() === $type);
        }

        return $types;
    }

    /**
     * @param string|null   $participantType
     * @param DateTime|null $dateTime If set, results are limited only to items valid in this moment.
     * @param bool|null     $recursive
     *
     * @param bool|null     $onlyPublic
     *
     * @return Collection<RegistrationsRange>
     */
    public function getRegistrationRangesByTypeString(
        ?string $participantType = null,
        ?DateTime $dateTime = null,
        ?bool $recursive = false,
        ?bool $onlyPublic = false
    ): Collection {
        if (null !== $participantType || null !== $dateTime || true === $onlyPublic) {
            return $this->getRegistrationRanges(null, $dateTime, $recursive, $onlyPublic)->filter(
                fn(RegistrationsRange $range) => $range->isApplicableByTypeOfType($participantType)
            );
        }

        return $this->getRegistrationRanges();
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
        if (null !== $event && !$this->getSubEvents()->contains($event)) {
            $this->getSubEvents()->add($event);
            $event->setSuperEvent($this);
        }
    }

    public function removeSubEvent(?Event $event): void
    {
        if (null !== $event && $this->getSubEvents()->removeElement($event)) {
            $event->setSuperEvent(null);
        }
    }

    public function getCapacity(?ParticipantType $participantType = null, ?DateTime $dateTime = null): ?int
    {
        $capacity = null;
        foreach ($this->getRegistrationRanges($participantType, $dateTime) as $range) {
            assert($range instanceof RegistrationsRange);
            if (null !== $range->getCapacity()) {
                $capacity += $range->getCapacity();
            }
        }

        return $capacity;
    }

    public function getMaxCapacity(?ParticipantType $participantType = null, ?DateTime $dateTime = null): ?int
    {
        $capacity = null;
        foreach ($this->getRegistrationRanges($participantType, $dateTime) as $range) {
            assert($range instanceof RegistrationsRange);
            if (null !== $range->getCapacity() || null !== $range->getCapacityOverflowLimit()) {
                $capacity += $range->getCapacity();
                $capacity += $range->getCapacityOverflowLimit();
            }
        }

        return $capacity;
    }

    public function addParticipantFlagInEventConnection(?ParticipantFlagRange $participantFlagInEventConnection): void
    {
        if ($participantFlagInEventConnection && !$this->getParticipantFlagRanges()->contains($participantFlagInEventConnection)) {
            $this->getParticipantFlagRanges()->add($participantFlagInEventConnection);
            $participantFlagInEventConnection->setEvent($this);
        }
    }

    public function getParticipantFlagRanges(
        ParticipantType $participantType = null,
        ?ParticipantFlag $participantFlag = null,
        bool $onlyPublic = false
    ): Collection {
        $connections = $this->participantFlagRanges ?? new ArrayCollection();
        if (null !== $participantType) {
            $connections = $connections->filter(
                fn(ParticipantFlagRange $conn) => $conn->getParticipantType() && $participantType->getId() === $conn->getParticipantType()->getId()
            );
        }
        if (null !== $participantFlag) {
            $connections = $connections->filter(
                fn(ParticipantFlagRange $conn) => $conn->getParticipantFlag() && $participantFlag->getId() === $conn->getParticipantFlag()->getId()
            );
        }
        if ($onlyPublic) {
            $connections = $connections->filter(fn(ParticipantFlagRange $conn) => $conn->isPublicOnWeb());
        }

        return $connections;
    }

    public function removeParticipantFlagInEventConnection(?ParticipantFlagRange $participantFlagInEventConnection): void
    {
        if ($participantFlagInEventConnection && $this->getParticipantFlagRanges()->removeElement($participantFlagInEventConnection)) {
            $participantFlagInEventConnection->setEvent(null);
        }
    }

    public function removeRegistrationRange(?RegistrationsRange $registrationRange): void
    {
        if (null !== $registrationRange) {
            $this->registrationRanges->removeElement($registrationRange);
        }
    }

    public function getDeposit(ParticipantType $eventParticipantType): ?int
    {
        return $this->getDepositOfEvent($eventParticipantType);
    }

    public function getDepositOfEvent(ParticipantType $participantType, ?DateTime $dateTime = null): ?int
    {
        $total = null;
        foreach ($this->getRegistrationRanges($participantType, $dateTime) as $range) {
            assert($range instanceof RegistrationsRange);
            if (null !== $range->getDepositValue()) {
                $total += $range->getDepositValue();
            }
            if ($range->isRelative() && null !== $this->getSuperEvent()) {
                $total += $this->getSuperEvent()->getDeposit($participantType);
            }
        }

        return null !== $total && $total <= 0 ? 0 : $total;
    }

    public function addWebContent(?EventWebContent $eventWebContent): void
    {
        if (null !== $eventWebContent && !$this->getWebContents()->contains($eventWebContent)) {
            $this->removeWebContent($this->getWebContent($eventWebContent->getType()));
            $this->getWebContents()->add($eventWebContent);
        }
    }

    public function getWebContents(?string $type = null, ?bool $recursive = false): Collection
    {
        if (null !== $type) {
            $contents = $this->getWebContents()->filter(fn(EventWebContent $webContent) => $type === $webContent->getType());

            return $recursive && $contents->count() < 1 && $this->getSuperEvent() ? $this->getSuperEvent()->getWebContents($type) : $contents;
        }

        return $this->webContents ?? new ArrayCollection();
    }

    public function removeWebContent(?EventWebContent $eventWebContent): void
    {
        $this->getWebContents()->removeElement($eventWebContent);
    }

    public function getWebContent(?string $type = 'html'): ?EventWebContent
    {
        $webContent = $this->getWebContents($type, true)->first();

        return $webContent instanceof EventWebContent ? $webContent : null;
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

    public function getAllowedFlagsAggregatedByType(?ParticipantType $eventParticipantType = null, bool $onlyPublic = false): array
    {
        $flags = [];
        foreach ($this->getParticipantFlagRanges($eventParticipantType, null, $onlyPublic) as $flagInEvent) {
            if ($flagInEvent instanceof ParticipantFlagRange && $flag = $flagInEvent->getParticipantFlag()) {
                $flagTypeSlug = $flag->getParticipantFlagType() ? $flag->getParticipantFlagType()->getSlug() : '0';
                $flags[$flagTypeSlug]['flagType'] = $flag->getParticipantFlagType();
                $flags[$flagTypeSlug]['flags'][] = $flag;
            }
        }

        return $flags;
    }

    /**
     * True if registrations for specified participant type (or any if not specified) is allowed in some datetime (or now if not specified).
     *
     * @param ParticipantType $participantType
     * @param DateTime|null   $dateTime
     *
     * @return bool
     */
    public function isRegistrationsAllowed(?ParticipantType $participantType = null, ?DateTime $dateTime = null): bool
    {
        return $this->getRegistrationRanges($participantType, $dateTime)->count() > 0;
    }

    public function __toString(): string
    {
        return $this->getExtendedName();
    }

    /**
     * @param bool                 $range
     * @param bool                 $price
     * @param ParticipantType|null $participantType
     *
     * @return string
     * @noinspection IsEmptyFunctionUsageInspection
     */
    public function getExtendedName(bool $range = true, bool $price = false, ?ParticipantType $participantType = null): string
    {
        $name = $this->getName();
        if ($range && !empty($rangeString = $this->getRangeAsText())) {
            $name .= " ($rangeString)";
        }
        if ($price && !empty($eventPrice = $this->getPrice($participantType))) {
            $name .= " ($eventPrice,- Kč)";
        }

        return $name;
    }

    public function getPrice(?ParticipantType $participantType, ?DateTime $dateTime = null): ?int
    {
        $total = null;
        if (null === $participantType) {
            return null;
        }
        foreach ($this->getRegistrationRanges($participantType, $dateTime) as $range) {
            assert($range instanceof RegistrationsRange);
            if (null !== $range->getNumericValue()) {
                $total += $range->getNumericValue();
            }
            if ($range->isRelative() && null !== $this->getSuperEvent()) {
                $total += $this->getSuperEvent()->getPrice($participantType);
            }
        }

        return null !== $total && $total <= 0 ? 0 : $total;
    }

    /**
     * Gets capacity of participant flag in event (for given participant type).
     *
     * @param ParticipantFlag|null $participantFlag
     * @param ParticipantType|null $participantType
     *
     * @return int Capacity of flag in event for participant type (NULL if not limited).
     */
    public function getParticipantFlagCapacity(?ParticipantFlag $participantFlag, ?ParticipantType $participantType): ?int
    {
        $allowedAmount = 0;
        foreach ($this->getParticipantFlagRanges($participantType, $participantFlag) as $flagInEventConnection) {
            if ($flagInEventConnection instanceof ParticipantFlagRange) {
                if (null === $flagInEventConnection->getCapacity()) {
                    return null;
                }
                $allowedAmount += $flagInEventConnection->getCapacity();
            }
        }

        return $allowedAmount;
    }

    public function removeFlagConnection(?EventFlagConnection $flagConnection): void
    {
        if (null !== $flagConnection && $this->getFlagConnections()->removeElement($flagConnection)) {
            $flagConnection->setEvent(null);
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

    public function isSuperEventRequired(?ParticipantType $participantType, ?DateTime $dateTime = null): bool
    {
        return $this->getRegistrationRanges($participantType, $dateTime)->exists(
            fn(RegistrationsRange $price) => $price->isSuperEventRequired()
        );
    }
}
