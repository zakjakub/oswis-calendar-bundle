<?php
/**
 * @noinspection PhpUnused
 * @noinspection MethodShouldBeFinalInspection
 */

namespace OswisOrg\OswisCalendarBundle\Entity\Participant;

use OswisOrg\OswisCalendarBundle\Entity\NonPersistent\EventCapacity;
use OswisOrg\OswisCalendarBundle\Entity\NonPersistent\EventPrice;
use OswisOrg\OswisCalendarBundle\Traits\Entity\EventCapacityTrait;
use OswisOrg\OswisCalendarBundle\Traits\Entity\EventPriceTrait;
use OswisOrg\OswisCoreBundle\Entity\NonPersistent\Publicity;
use OswisOrg\OswisCoreBundle\Interfaces\Common\BasicInterface;
use OswisOrg\OswisCoreBundle\Traits\Common\BasicTrait;
use OswisOrg\OswisCoreBundle\Traits\Common\EntityPublicTrait;

/**
 * @Doctrine\ORM\Mapping\Entity()
 * @Doctrine\ORM\Mapping\Table(name="calendar_participant_flag_range")
 * @Doctrine\ORM\Mapping\Cache(usage="NONSTRICT_READ_WRITE", region="calendar_participant")
 */
class ParticipantFlagRange implements BasicInterface
{
    use BasicTrait;
    use EntityPublicTrait;
    use EventCapacityTrait;
    use EventPriceTrait;

    /**
     * @Doctrine\ORM\Mapping\ManyToOne(
     *     targetEntity="OswisOrg\OswisCalendarBundle\Entity\Participant\ParticipantFlag",
     *     fetch="EAGER"
     * )
     * @Doctrine\ORM\Mapping\JoinColumn(nullable=true)
     */
    protected ?ParticipantFlag $flag = null;

    /**
     * @var int Number of usages of flag (must be updated from service!).
     * @Doctrine\ORM\Mapping\Column(type="integer", nullable=false)
     */
    protected int $baseUsage = 0;

    public function __construct(
        ?ParticipantFlag $flag = null,
        ?EventCapacity $eventCapacity = null,
        ?EventPrice $eventPrice = null,
        ?Publicity $publicity = null
    ) {
        $this->setFlag($flag);
        $this->setEventCapacity($eventCapacity);
        $this->setEventPrice($eventPrice);
        $this->setFieldsFromPublicity($publicity);
    }

    public function getPrice(): int
    {
        return $this->price ?? 0;
    }

    public function getDepositValue(): int
    {
        return $this->depositValue ?? 0;
    }

    public function getRemainingCapacity(bool $max = false): ?int
    {
        $capacity = $this->getCapacity($max);

        return null === $capacity ? null : ($capacity - $this->getBaseUsage());
    }

    public function getBaseUsage(): int
    {
        return $this->baseUsage;
    }

    public function setBaseUsage(int $baseUsage): void
    {
        $this->baseUsage = $baseUsage;
    }

    public function containsFlag(?ParticipantFlag $flag = null): bool
    {
        return null === $flag ? true : $this->getFlag() && $this->getFlag() === $flag;
    }

    public function getFlag(): ?ParticipantFlag
    {
        return $this->flag;
    }

    public function setFlag(?ParticipantFlag $flag): void
    {
        $this->flag = $flag;
    }

    public function containsFlagOfType(?ParticipantFlagType $flagType = null): bool
    {
        return null === $flagType ? true : $this->getFlagType() && $this->getFlagType() === $flagType;
    }

    public function getFlagType(): ?ParticipantFlagType
    {
        return $this->getFlag() ? $this->getFlag()->getFlagType() : null;
    }

    public function containsFlagOfTypeString(?string $flagType = null): bool
    {
        return null === $flagType ? true : $this->getFlagType() && $this->getFlagType()->getType() === $flagType;
    }
}
