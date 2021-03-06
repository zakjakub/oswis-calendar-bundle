<?php

namespace OswisOrg\OswisCalendarBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use OswisOrg\OswisCalendarBundle\Entity\EventAttendeeFlag;
use OswisOrg\OswisCalendarBundle\Entity\Participant\RegistrationFlagCategory;
use OswisOrg\OswisCalendarBundle\Entity\Registration\FlagCategory;
use Psr\Log\LoggerInterface;

class FlagCategoryService
{
    protected EntityManagerInterface $em;

    protected LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    final public function create(FlagCategory $flagType): ?FlagCategory
    {
        try {
            $this->em->persist($flagType);
            $this->em->flush();
            $this->logger->info('CREATE: Created flag category (by service): '.$flagType->getId().' '.$flagType->getShortName().'.');

            return $flagType;
        } catch (Exception $e) {
            $this->logger->info('ERROR: Flag category type not created (by service): '.$e->getMessage());

            return null;
        }
    }
}
