<?php
/**
 * @noinspection MethodShouldBeFinalInspection
 * @noinspection PhpUnused
 */

namespace Zakjakub\OswisCalendarBundle\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Zakjakub\OswisCalendarBundle\Entity\EventParticipant\EventParticipantType;

class EventParticipantTypeRepository extends EntityRepository
{
    public const CRITERIA_ID = 'id';
    public const CRITERIA_SLUG = 'slug';
    public const CRITERIA_TYPE_OF_TYPE = 'participantTypeOfType';
    public const CRITERIA_ONLY_PUBLIC_ON_WEB = 'onlyPublicOnWeb';

    public function findOneBy(array $criteria, array $orderBy = null): ?EventParticipantType
    {
        $participantType = parent::findOneBy($criteria, $orderBy);

        return $participantType instanceof EventParticipantType ? $participantType : null;
    }

    public function getEventParticipantType(?array $opts = []): ?EventParticipantType
    {
        try {
            $participantType = $this->getQueryBuilder($opts)->getQuery()->getOneOrNullResult();
        } catch (Exception $e) {
            return null;
        }

        return $participantType instanceof EventParticipantType ? $participantType : null;
    }

    public function getQueryBuilder(array $opts = [], ?int $limit = null, ?int $offset = null): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('ept');
        $this->addIdQuery($queryBuilder, $opts);
        $this->addSlugQuery($queryBuilder, $opts);
        $this->addTypeOfTypeQuery($queryBuilder, $opts);
        $this->addOnlyPublicOnWebQuery($queryBuilder, $opts);
        $this->addLimit($queryBuilder, $limit, $offset);
        $this->addOrderBy($queryBuilder, true);

        return $queryBuilder;
    }

    private function addIdQuery(QueryBuilder $queryBuilder, array $opts = []): void
    {
        if (!empty($opts[self::CRITERIA_ID])) {
            $queryBuilder->andWhere(' ept.id = :id ')->setParameter('id', $opts[self::CRITERIA_ID]);
        }
    }

    private function addSlugQuery(QueryBuilder $queryBuilder, array $opts = []): void
    {
        if (!empty($opts[self::CRITERIA_SLUG])) {
            $queryBuilder->andWhere(' ept.slug = :slug ')->setParameter('slug', $opts[self::CRITERIA_SLUG]);
        }
    }

    private function addTypeOfTypeQuery(QueryBuilder $queryBuilder, array $opts = []): void
    {
        if (!empty($opts[self::CRITERIA_TYPE_OF_TYPE]) && is_string($opts[self::CRITERIA_TYPE_OF_TYPE])) {
            $queryBuilder->andWhere('ept.type = :type_type');
            $queryBuilder->setParameter('type_type', $opts[self::CRITERIA_TYPE_OF_TYPE]);
        }
    }

    private function addOnlyPublicOnWebQuery(QueryBuilder $queryBuilder, array $opts = []): void
    {
        if (!empty($opts[self::CRITERIA_ONLY_PUBLIC_ON_WEB])) {
            $queryBuilder->andWhere('ept.public_on_web = true');
        }
    }

    private function addLimit(QueryBuilder $queryBuilder, ?int $limit = null, ?int $offset = null): void
    {
        if (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }
        if (null !== $offset) {
            $queryBuilder->setFirstResult($offset);
        }
    }

    private function addOrderBy(QueryBuilder $queryBuilder, bool $name = true): void
    {
        if ($name) {
            $queryBuilder->addOrderBy('ept.name', 'ASC');
        }
        $queryBuilder->addOrderBy('ept.id', 'ASC');
    }

    public function getEventParticipantTypes(?array $opts = [], ?int $limit = null, ?int $offset = null): Collection
    {
        return new ArrayCollection($this->getQueryBuilder($opts, $limit, $offset)->getQuery()->getResult());
    }
}
