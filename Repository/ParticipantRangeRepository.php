<?php
/**
 * @noinspection MethodShouldBeFinalInspection
 */

namespace OswisOrg\OswisCalendarBundle\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Exception;
use OswisOrg\OswisCalendarBundle\Entity\Participant\ParticipantRange;
use OswisOrg\OswisCalendarBundle\Entity\Registration\RegRange;

class ParticipantRangeRepository extends EntityRepository
{
    public const CRITERIA_ID = 'id';
    public const CRITERIA_RANGE = 'range';
    public const CRITERIA_INCLUDE_DELETED = 'includeDeleted';

    public function findOneBy(array $criteria, ?array $orderBy = null): ?ParticipantRange
    {
        $result = parent::findOneBy($criteria, $orderBy);

        return $result instanceof ParticipantRange ? $result : null;
    }

    public function getRangesConnections(array $opts = [], ?int $limit = null, ?int $offset = null): Collection
    {
        $queryBuilder = $this->getRangesConnectionsQueryBuilder($opts, $limit, $offset);

        return new ArrayCollection($queryBuilder->getQuery()->getResult());
    }

    public function getRangesConnectionsQueryBuilder(array $opts = [], ?int $limit = null, ?int $offset = null): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('participant_range');
        $this->addIdQuery($queryBuilder, $opts);
        $this->addRangeQuery($queryBuilder, $opts);
        $this->addIncludeDeletedQuery($queryBuilder, $opts);
        $this->addLimit($queryBuilder, $limit, $offset);
        $this->addOrderBy($queryBuilder);

        return $queryBuilder;
    }

    private function addIdQuery(QueryBuilder $queryBuilder, array $opts = []): void
    {
        if (!empty($opts[self::CRITERIA_ID])) {
            $queryBuilder->andWhere(' participant_range.id = :id ')->setParameter('id', $opts[self::CRITERIA_ID]);
        }
    }

    private function addRangeQuery(QueryBuilder $queryBuilder, array $opts = []): void
    {
        if (!empty($opts[self::CRITERIA_RANGE]) && $opts[self::CRITERIA_RANGE] instanceof RegRange) {
            $queryBuilder->andWhere('participant_range.range = :range_id');
            $queryBuilder->setParameter('range_id', $opts[self::CRITERIA_RANGE]->getId());
        }
    }

    private function addIncludeDeletedQuery(QueryBuilder $queryBuilder, array $opts = []): void
    {
        if (empty($opts[self::CRITERIA_INCLUDE_DELETED])) {
            $queryBuilder->andWhere('participant_range.deletedAt IS NULL');
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

    private function addOrderBy(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->addOrderBy('participant_range.id', 'ASC');
    }

    public function getFlagRangeConnection(?array $opts = []): ?ParticipantRange
    {
        try {
            $rangeConnection = $this->getRangesConnectionsQueryBuilder($opts)->getQuery()->getOneOrNullResult();
        } catch (Exception $e) {
            return null;
        }

        return $rangeConnection instanceof ParticipantRange ? $rangeConnection : null;
    }
}



