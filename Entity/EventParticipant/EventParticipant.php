<?php /** @noinspection PhpUnused */

namespace Zakjakub\OswisCalendarBundle\Entity\EventParticipant;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Zakjakub\OswisAddressBookBundle\Entity\AbstractClass\AbstractContact;
use Zakjakub\OswisCalendarBundle\Entity\Event\Event;
use Zakjakub\OswisCalendarBundle\Exceptions\EventCapacityExceededException;
use Zakjakub\OswisCoreBundle\Exceptions\PriceInvalidArgumentException;
use Zakjakub\OswisCoreBundle\Filter\SearchAnnotation as Searchable;
use Zakjakub\OswisCoreBundle\Traits\Entity\BasicEntityTrait;
use Zakjakub\OswisCoreBundle\Traits\Entity\BasicMailConfirmationTrait;
use Zakjakub\OswisCoreBundle\Traits\Entity\DeletedTrait;
use Zakjakub\OswisCoreBundle\Traits\Entity\InfoMailSentTrait;
use function assert;

/**
 * Participation of contact in event (attendee, sponsor, organizer, guest, partner...).
 *
 * @Doctrine\ORM\Mapping\Entity(
 *     repositoryClass="Zakjakub\OswisCalendarBundle\Repository\EventParticipantRepository"
 * )
 * @Doctrine\ORM\Mapping\Table(name="calendar_event_participant")
 * @ApiResource(
 *   attributes={
 *     "filters"={"search"},
 *     "access_control"="is_granted('ROLE_MANAGER')"
 *   },
 *   collectionOperations={
 *     "get"={
 *       "access_control"="is_granted('ROLE_MANAGER')",
 *       "normalization_context"={"groups"={"calendar_event_participants_get"}, "enable_max_depth"=true},
 *     },
 *     "post"={
 *       "access_control"="is_granted('ROLE_MANAGER')",
 *       "denormalization_context"={"groups"={"calendar_event_participants_post"}, "enable_max_depth"=true}
 *     }
 *   },
 *   itemOperations={
 *     "get"={
 *       "access_control"="is_granted('ROLE_MANAGER')",
 *       "normalization_context"={"groups"={"calendar_event_participant_get"}, "enable_max_depth"=true},
 *     },
 *     "put"={
 *       "access_control"="is_granted('ROLE_MANAGER')",
 *       "denormalization_context"={"groups"={"calendar_event_participant_put"}, "enable_max_depth"=true}
 *     },
 *     "delete"={
 *       "access_control"="is_granted('ROLE_MANAGER')",
 *       "denormalization_context"={"groups"={"calendar_event_participant_delete"}, "enable_max_depth"=true}
 *     }
 *   }
 * )
 * @ApiFilter(OrderFilter::class, properties={
 *     "id": "ASC",
 *     "createdDateTime",
 *     "activeRevision.event.id",
 *     "activeRevision.event.activeRevision.name",
 *     "activeRevision.event.activeRevision.shortName",
 *     "activeRevision.event.activeRevision.slug",
 *     "activeRevision.event.activeRevision.color",
 *     "activeRevision.event.activeRevision.startDateTime",
 *     "activeRevision.event.activeRevision.endDateTime",
 *     "activeRevision.event.eventType.id",
 *     "activeRevision.event.eventType.activeRevision.name",
 *     "activeRevision.event.eventType.activeRevision.shortName",
 *     "activeRevision.event.eventType.activeRevision.slug",
 *     "activeRevision.event.eventType.activeRevision.color",
 *     "activeRevision.contact.id",
 *     "activeRevision.contact.contactName",
 *     "activeRevision.contact.sortableName",
 *     "activeRevision.contact.contactDetails.content",
 *     "activeRevision.eventParticipantFlagConnections.eventParticipantFlag.name",
 *     "activeRevision.eventParticipantFlagConnections.eventParticipantFlag.shortName",
 *     "activeRevision.eventParticipantFlagConnections.eventParticipantFlag.slug"
 * })
 * @ApiFilter(SearchFilter::class, properties={
 *     "id": "iexact",
 *     "activeRevision.event.id": "iexact",
 *     "activeRevision.event.activeRevision.name": "ipartial",
 *     "activeRevision.event.activeRevision.shortName": "ipartial",
 *     "activeRevision.event.activeRevision.slug": "ipartial",
 *     "activeRevision.event.activeRevision.color": "ipartial",
 *     "activeRevision.event.activeRevision.startDateTime": "ipartial",
 *     "activeRevision.event.activeRevision.endDateTime": "ipartial",
 *     "activeRevision.event.eventType.id": "iexact",
 *     "activeRevision.event.eventType.activeRevision.name": "ipartial",
 *     "activeRevision.event.eventType.activeRevision.shortName": "ipartial",
 *     "activeRevision.event.eventType.activeRevision.slug": "ipartial",
 *     "activeRevision.event.eventType.activeRevision.color": "ipartial",
 *     "activeRevision.contact.id": "iexact",
 *     "activeRevision.contact.contactName": "ipartial",
 *     "activeRevision.contact.contactDetails.content": "ipartial",
 *     "activeRevision.eventParticipantFlagConnections.eventParticipantFlag.name": "ipartial",
 *     "activeRevision.eventParticipantFlagConnections.eventParticipantFlag.shortName": "ipartial",
 *     "activeRevision.eventParticipantFlagConnections.eventParticipantFlag.slug": "ipartial"
 * })
 * @ApiFilter(ExistsFilter::class, properties={"deleted"})
 * @Searchable({
 *     "id",
 *     "activeRevision.event.id",
 *     "activeRevision.event.activeRevision.name",
 *     "activeRevision.event.activeRevision.shortName",
 *     "activeRevision.event.activeRevision.slug",
 *     "activeRevision.event.activeRevision.color",
 *     "activeRevision.event.activeRevision.startDateTime",
 *     "activeRevision.event.activeRevision.endDateTime",
 *     "activeRevision.event.eventType.id",
 *     "activeRevision.event.eventType.activeRevision.name",
 *     "activeRevision.event.eventType.activeRevision.shortName",
 *     "activeRevision.event.eventType.activeRevision.slug",
 *     "activeRevision.event.eventType.activeRevision.color",
 *     "activeRevision.contact.id",
 *     "activeRevision.contact.contactName",
 *     "activeRevision.contact.contactDetails.content",
 *     "activeRevision.eventParticipantFlagConnections.eventParticipantFlag.name",
 *     "activeRevision.eventParticipantFlagConnections.eventParticipantFlag.shortName",
 *     "activeRevision.eventParticipantFlagConnections.eventParticipantFlag.slug"
 * })
 * @Doctrine\ORM\Mapping\Cache(usage="NONSTRICT_READ_WRITE", region="calendar_event_participant")
 */
class EventParticipant
{
    use BasicEntityTrait;
    use DeletedTrait;
    use BasicMailConfirmationTrait;
    use InfoMailSentTrait;

    /**
     * Type of relation between contact and event - attendee, staff....
     * @Doctrine\ORM\Mapping\ManyToOne(
     *     targetEntity="Zakjakub\OswisCalendarBundle\Entity\EventParticipant\EventParticipantType",
     *     cascade={"all"},
     *     fetch="EAGER"
     * )
     * @Doctrine\ORM\Mapping\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    protected ?EventParticipantType $eventParticipantType = null;

    /**
     * @Doctrine\ORM\Mapping\ManyToMany(
     *     targetEntity="Zakjakub\OswisCalendarBundle\Entity\EventParticipant\EventParticipantNote",
     *     cascade={"all"},
     *     fetch="EAGER"
     * )
     * @Doctrine\ORM\Mapping\JoinTable(
     *     name="calendar_event_participant_note_connection",
     *     joinColumns={@Doctrine\ORM\Mapping\JoinColumn(name="event_participant_id", referencedColumnName="id")},
     *     inverseJoinColumns={@Doctrine\ORM\Mapping\JoinColumn(name="event_participant_note_id", referencedColumnName="id", unique=true)}
     * )
     */
    protected ?Collection $eventParticipantNotes = null;

    /**
     * @Doctrine\ORM\Mapping\OneToMany(
     *     targetEntity="Zakjakub\OswisCalendarBundle\Entity\EventParticipant\EventParticipantPayment",
     *     cascade={"all"},
     *     mappedBy="eventParticipant",
     *     fetch="EAGER"
     * )
     * @MaxDepth(1)
     */
    protected ?Collection $eventParticipantPayments = null;

    /**
     * @Doctrine\ORM\Mapping\OneToMany(
     *     targetEntity="Zakjakub\OswisCalendarBundle\Entity\EventParticipant\EventParticipantFlagNewConnection",
     *     cascade={"all"},
     *     mappedBy="eventParticipant",
     *     fetch="EAGER"
     * )
     */
    protected ?Collection $eventParticipantFlagConnections = null;

    /**
     * Related contact (person or organization).
     * @Doctrine\ORM\Mapping\ManyToOne(
     *     targetEntity="Zakjakub\OswisAddressBookBundle\Entity\AbstractClass\AbstractContact",
     *     cascade={"all"},
     *     fetch="EAGER"
     * )
     * @Doctrine\ORM\Mapping\JoinColumn(nullable=true)
     */
    protected ?AbstractContact $contact = null;

    /**
     * Related event.
     * @Doctrine\ORM\Mapping\ManyToOne(
     *     targetEntity="Zakjakub\OswisCalendarBundle\Entity\Event\Event",
     *     inversedBy="eventParticipants",
     *     fetch="EAGER"
     * )
     * @Doctrine\ORM\Mapping\JoinColumn(nullable=true)
     */
    protected ?Event $event = null;

    /**
     * EventAttendee constructor.
     *
     * @param AbstractContact|null      $contact
     * @param Event|null                $event
     * @param EventParticipantType|null $eventParticipantType
     * @param Collection|null           $eventParticipantFlagConnections
     * @param Collection|null           $eventParticipantNotes
     * @param DateTime|null             $deleted
     *
     * @throws EventCapacityExceededException
     */
    public function __construct(
        ?AbstractContact $contact = null,
        ?Event $event = null,
        ?EventParticipantType $eventParticipantType = null,
        ?Collection $eventParticipantFlagConnections = null,
        ?Collection $eventParticipantNotes = null,
        ?DateTime $deleted = null
    ) {
        $this->setContact($contact);
        $this->setEvent($event);
        $this->setEventParticipantType($eventParticipantType);
        $this->setEventParticipantNotes($eventParticipantNotes);
        $this->setEventParticipantPayments(new ArrayCollection());
        $this->setEventParticipantFlagConnections($eventParticipantFlagConnections);
        $this->setDeleted($deleted);
    }

    /**
     * @param EventParticipantFlagNewConnection|null $newConnection
     *
     * @throws EventCapacityExceededException
     */
    final public function addEventParticipantFlagConnection(?EventParticipantFlagNewConnection $newConnection): void
    {
        if (!$newConnection) {
            return;
        }
//        $eventParticipantFlag = $newConnection->getEventParticipantFlag();
//        $eventParticipantType = $this->getEventParticipantType();
//        try {
//            $event = $this->getEvent();
//            if ($event && $event->getAllowedEventParticipantFlagRemainingAmount($eventParticipantFlag, $eventParticipantType) === 0) {
//                throw new EventCapacityExceededException(
//                    'Byla překročena kapacita u příznaku'.($eventParticipantFlag ? ' '.$eventParticipantFlag->getName() : '').'.'
//                );
//            }
//        } catch (RevisionMissingException $e) {
//        }
        if ($newConnection && !$this->eventParticipantFlagConnections->contains($newConnection)) {
            $this->eventParticipantFlagConnections->add($newConnection);
            $newConnection->setEventParticipant($this);
        }
    }

    /**
     * @param EventParticipantFlagNewConnection|null $eventContactFlagConnection
     *
     * @throws EventCapacityExceededException
     */
    final public function removeEventParticipantFlagConnection(?EventParticipantFlagNewConnection $eventContactFlagConnection): void
    {
        if ($eventContactFlagConnection && $this->eventParticipantFlagConnections->removeElement($eventContactFlagConnection)) {
            $eventContactFlagConnection->setEventParticipant(null);
        }
    }

    /**
     * Remove notes where no content is present.
     */
    final public function removeEmptyEventParticipantNotes(): void
    {
        $this->setEventParticipantNotes($this->getEventParticipantNotes()->filter(fn(EventParticipantNote $note): bool => !empty($note->getTextValue())));
    }

    /**
     * @return Collection
     */
    final public function getEventParticipantNotes(): Collection
    {
        return $this->eventParticipantNotes ?? new ArrayCollection();
    }

    /**
     * @param Collection|null $newEventParticipantNotes
     */
    final public function setEventParticipantNotes(?Collection $newEventParticipantNotes): void
    {
        $this->eventParticipantNotes = $newEventParticipantNotes ?? new ArrayCollection();
    }

    final public function removeEventParticipantNote(?EventParticipantNote $eventParticipantNote): void
    {
        if ($eventParticipantNote) {
            $this->eventParticipantNotes->removeElement($eventParticipantNote);
        }
    }

    final public function addEventParticipantNote(?EventParticipantNote $eventParticipantNote): void
    {
        if ($eventParticipantNote && !$this->eventParticipantNotes->contains($eventParticipantNote)) {
            $this->eventParticipantNotes->add($eventParticipantNote);
        }
    }

    /**
     * @return int
     * @throws PriceInvalidArgumentException
     */
    final public function getRemainingRest(): int
    {
        return $this->getPriceRest() - $this->getPaidPrice() + $this->getPriceDeposit();
    }

    /**
     * @return int
     * @throws PriceInvalidArgumentException
     */
    final public function getPriceRest(): int
    {
        return $this->getPrice() - $this->getPriceDeposit();
    }

    /**
     * @return int
     * @throws PriceInvalidArgumentException
     */
    final public function getPrice(): int
    {
        if (!$this->getEvent() || !$this->getEventParticipantType()) {
            throw new PriceInvalidArgumentException();
        }
        $price = $this->getEvent()->getPrice($this->getEventParticipantType()) + $this->getFlagsPrice();

        return $price < 0 ? 0 : $price;
    }

    final public function getEvent(): ?Event
    {
        return $this->event;
    }

    /**
     * @param Event|null $event
     *
     * @throws EventCapacityExceededException
     */
    final public function setEvent(?Event $event): void
    {
        if ($this->event && $event !== $this->event) {
            $this->event->removeEventParticipant($this);
        }
        $this->event = $event;
        if ($event && $this->event !== $event) {
            $event->addEventParticipant($this);
        }
    }

    /**
     * @return EventParticipantType|null
     */
    final public function getEventParticipantType(): ?EventParticipantType
    {
        return $this->eventParticipantType;
    }

    /**
     * @param EventParticipantType|null $eventParticipantType
     */
    final public function setEventParticipantType(?EventParticipantType $eventParticipantType): void
    {
        $this->eventParticipantType = $eventParticipantType;
    }

    final public function getFlagsPrice(?EventParticipantFlagType $eventParticipantFlagType = null): int
    {
        $price = 0;
        foreach ($this->getEventParticipantFlags($eventParticipantFlagType) as $flag) {
            assert($flag instanceof EventParticipantFlag);
            $price += $flag->getPrice();
        }

        return $price;
    }

    final public function getEventParticipantFlags(?EventParticipantFlagType $eventParticipantFlagType = null): Collection
    {
        return $this->getEventParticipantFlagConnections($eventParticipantFlagType)->map(
            fn(EventParticipantFlagNewConnection $connection) => $connection->getEventParticipantFlag()
        );
    }

    final public function getEventParticipantFlagConnections(?EventParticipantFlagType $eventParticipantFlagType = null): Collection
    {
        if (!$eventParticipantFlagType) {
            return $this->eventParticipantFlagConnections ?? new ArrayCollection();
        }

        return $this->eventParticipantFlagConnections->filter(
            static function (EventParticipantFlagNewConnection $eventParticipantFlagConnection) use ($eventParticipantFlagType) {
                try {
                    $flag = $eventParticipantFlagConnection->getEventParticipantFlag();
                    $type = $flag ? $flag->getEventParticipantFlagType() : null;

                    return $type && $type->getId() === $eventParticipantFlagType->getId();
                } catch (Exception $e) {
                    return false;
                }
            }
        );
    }

    /**
     * @param Collection|null $newConnections
     *
     * @throws EventCapacityExceededException
     */
    final public function setEventParticipantFlagConnections(?Collection $newConnections): void
    {
        $this->eventParticipantFlagConnections ??= new ArrayCollection();
        $newConnections ??= new ArrayCollection();
        foreach ($this->eventParticipantFlagConnections as $oldConnection) {
            if (!$newConnections->contains($oldConnection)) {
                $this->removeEventParticipantFlagConnection($oldConnection);
            }
        }
        foreach ($newConnections as $newConnection) {
            if (!$this->eventParticipantFlagConnections->contains($newConnection)) {
                $this->addEventParticipantFlagConnection($newConnection);
            }
        }
    }

    /**
     * @return int
     * @throws PriceInvalidArgumentException
     */
    final public function getPriceDeposit(): int
    {
        if (!$this->getEvent() || !$this->getEventParticipantType()) {
            throw new PriceInvalidArgumentException();
        }
        $price = $this->getEvent()->getDeposit($this->getEventParticipantType());

        return $price < 0 ? 0 : $price;
    }

    /**
     * @return int
     */
    final public function getPaidPrice(): int
    {
        $paid = 0;
        foreach ($this->getEventParticipantPayments() as $eventParticipantPayment) {
            assert($eventParticipantPayment instanceof EventParticipantPayment);
            $paid += $eventParticipantPayment->getNumericValue();
        }

        return $paid;
    }

    /**
     * @return Collection|null
     */
    final public function getEventParticipantPayments(): ?Collection
    {
        return $this->eventParticipantPayments ?? new ArrayCollection();
    }

    /**
     * @param Collection|null $newEventParticipantPayments
     */
    final public function setEventParticipantPayments(?Collection $newEventParticipantPayments): void
    {
        $this->eventParticipantPayments = $this->eventParticipantPayments ?? new ArrayCollection();
        $newEventParticipantPayments = $newEventParticipantPayments ?? new ArrayCollection();
        foreach ($this->eventParticipantPayments as $oldPayment) {
            if (!$newEventParticipantPayments->contains($oldPayment)) {
                $this->removeEventParticipantPayment($oldPayment);
            }
        }
        foreach ($newEventParticipantPayments as $newPayment) {
            if (!$this->eventParticipantPayments->contains($newPayment)) {
                $this->addEventParticipantPayment($newPayment);
            }
        }
    }

    /**
     * @return int
     * @throws PriceInvalidArgumentException
     */
    final public function getRemainingPrice(): int
    {
        return $this->getPrice() - $this->getPaidPrice();
    }

    /**
     * @return int
     * @throws PriceInvalidArgumentException
     */
    final public function getRemainingDeposit(): int
    {
        return $this->getPriceDeposit() - $this->getPaidPrice();
    }

    /**
     * @return float
     * @throws PriceInvalidArgumentException
     */
    final public function getPaidPricePercent(): float
    {
        return !$this->getPrice() ? 0 : ($this->getPaidPrice() / $this->getPrice());
    }

    /**
     * @param EventParticipantPayment|null $eventParticipantPayment
     */
    final public function removeEventParticipantPayment(?EventParticipantPayment $eventParticipantPayment): void
    {
        if ($eventParticipantPayment && $this->eventParticipantPayments->removeElement($eventParticipantPayment)) {
            $eventParticipantPayment->setEventParticipant(null);
        }
    }

    /**
     * @param EventParticipantPayment|null $eventParticipantPayment
     */
    final public function addEventParticipantPayment(?EventParticipantPayment $eventParticipantPayment): void
    {
        if ($eventParticipantPayment && !$this->eventParticipantPayments->contains($eventParticipantPayment)) {
            $this->eventParticipantPayments->add($eventParticipantPayment);
            $eventParticipantPayment->setEventParticipant($this);
        }
    }

    /**
     * @param DateTime|null $referenceDateTime
     *
     * @return bool
     */
    final public function hasActivatedContactUser(?DateTime $referenceDateTime = null): bool
    {
        try {
            return $this->getContact() && $this->getContact()->getContactPersons($referenceDateTime, true)->count() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    final public function getContact(): ?AbstractContact
    {
        return $this->contact;
    }

    final public function setContact(?AbstractContact $contact): void
    {
        $this->contact = $contact;
    }

    /**
     * Get variable symbol of this eventParticipant (default is cropped phone number).
     * @return string|null
     * @noinspection MethodShouldBeFinalInspection
     */
    public function getVariableSymbol(): ?string
    {
        $phone = preg_replace('/\s/', '', $this->getContact() ? $this->getContact()->getPhone() : null);

        return substr(trim($phone), strlen(trim($phone)) - 9, 9);
    }

    final public function destroyRevisions(): void
    {
    }
}
