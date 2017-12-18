<?php

namespace Clear01\DoctrineBehaviors\Sortable;

use Doctrine\Common\Collections\Criteria;

use Doctrine\ORM\QueryBuilder;
use Kdyby\Doctrine\Dql\Join;
use Kdyby\Doctrine\EntityManager;


class EntitySortingService
{
	/** @var  EntityManager */
	protected $em;

	/**
	 * EntitySortingService constructor.
	 * @param EntityManager $em
	 */
	public function __construct(EntityManager $em)
	{
		$this->em = $em;
	}


	public function moveBefore(string $scope, int $entityId, ?int $targetEntityId) : bool {
		/** @var EntityPosition $entityPosition */
		$entityPosition = $this->em->getRepository(EntityPosition::class)->findOneBy(['entityId' => $entityId, 'scope' => $scope]);
		if(!$entityPosition) {
			throw new \RuntimeException(sprintf('Entity position for entity with ID %d under scope %s not found!', $entityId, $scope));
		}
		if($targetEntityId) {
			/** @var EntityPosition $targetEntityPosition */
			$targetEntityPosition = $this->em->getRepository(EntityPosition::class)->findOneBy(['entityId' => $targetEntityId, 'scope' => $scope]);
			if(!$targetEntityPosition) {
				throw new \RuntimeException(sprintf('Entity position for entity with ID %d under scope %s not found!', $targetEntityId, $scope));
			}
		} else {
			$targetEntityPosition = null;
		}

		if($targetEntityPosition && $entityPosition->getPosition() == $targetEntityPosition->getPosition()) { // no change
			return false;
		}

		try {
			$this->em->beginTransaction();
			if (!$targetEntityPosition) {
				// moving to the end
				$maxPos = $this->em->createQuery('SELECT max(ep.position) FROM ' . EntityPosition::class . ' ep WHERE ep.scope = :scope')
					->setParameters(['scope' => $scope])->getSingleScalarResult();
				$this->em->createQuery('
					UPDATE ' . EntityPosition::class . ' ep SET ep.position = ep.position - 1 WHERE ep.scope = :scope AND ep.position > :positionThreshold 
				')->execute(['scope' => $scope, 'positionThreshold' => $entityPosition->getPosition()]);
				$this->em->createQuery('
					UPDATE ' . EntityPosition::class . ' ep SET ep.position = :newPosition WHERE ep.scope = :scope AND ep.entityId = :entityId 
				')->execute(['entityId' => $entityPosition->getEntityId(), 'scope' => $scope, 'newPosition' => $maxPos]);
			} elseif ($targetEntityPosition->getPosition() > $entityPosition->getPosition()) {
				// moving down
				$this->em->createQuery('
					UPDATE ' . EntityPosition::class . ' ep SET ep.position = ep.position - 1 WHERE ep.scope = :scope AND ep.position > :minPositionThreshold AMD ep.position <= :maxPositionThreshold 
				')->execute(['scope' => $scope, 'minPositionThreshold' => $entityPosition->getPosition(), 'maxPositionThreshold' => $targetEntityPosition->getPosition()]);
				$this->em->createQuery('
					UPDATE ' . EntityPosition::class . ' ep SET ep.position = :newPosition WHERE ep.scope = :scope AND ep.entityId = :entityId 
				')->execute(['entityId' => $entityPosition->getEntityId(), 'scope' => $scope, 'newPosition' => $targetEntityPosition->getPosition()]);
			} else {
				// moving up
				$this->em->createQuery('
					UPDATE ' . EntityPosition::class . ' ep SET ep.position = ep.position + 1 WHERE ep.scope = :scope AND ep.position >= :minPositionThreshold AMD ep.position < :maxPositionThreshold 
				')->execute(['scope' => $scope, 'minPositionThreshold' => $targetEntityPosition->getPosition(), 'maxPositionThreshold' => $entityPosition->getPosition()]);
				$this->em->createQuery('
					UPDATE ' . EntityPosition::class . ' ep SET ep.position = :newPosition WHERE ep.scope = :scope AND ep.entityId = :entityId 
				')->execute(['entityId' => $entityPosition->getEntityId(), 'scope' => $scope, 'newPosition' => $targetEntityPosition->getPosition()]);
			}
		} catch (\Throwable $e) {
			$this->em->rollback();
			throw $e;
		}
		$this->em->commit();

		return true;
	}

	public function checkPriorityPresence(string $scope, string $entityClass, $sortBy = ['id' => Criteria::ASC]) : void {
		if($this->em->getRepository($entityClass)->countBy([]) != $this->em->getRepository(EntityPosition::class)->countBy(['scope' => $scope])){
			$qb = $this->em->createQueryBuilder()
				->select('e.id as id, ep.entityId')
				->from($entityClass, 'e')
				->leftJoin(EntityPosition::class, 'ep', Join::WITH, 'ep.entityId = e.id')
				->where('ep.entityId IS NULL');
			foreach($sortBy as $col => $dir) {
				$qb->addOrderBy('e.' . $col, $dir);
			}
			$q = $qb->getQuery();
			$position = $this->em->createQuery('SELECT max(ep.position) FROM ' . EntityPosition::class . ' ep WHERE ep.scope = :scope')
				->setParameters(['scope' => $scope])->getSingleScalarResult();
			foreach($q->getArrayResult() as $result) {
				$position++;
				$ep = new EntityPosition(
					$scope, $result['id'], $position
				);
				$this->em->persist($ep);
			}
			try {
				$this->em->beginTransaction();
				$this->em->flush();
			} catch (\Throwable $e) {
				$this->em->rollback();
				throw $e;
			}
			$this->em->commit();
		}
	}

	public function applySorting(QueryBuilder $queryBuilder, string $scope, string $entityAlias, string $direction = Criteria::ASC) : void {
		$queryBuilder->leftJoin(EntityPosition::class, 'ep', Join::WITH, 'ep.entityId = ' . $entityAlias . '.id AND ep.scope = :scope')
			->setParameter('scope', $scope)
			->addOrderBy('ep.position', $direction);
	}
}