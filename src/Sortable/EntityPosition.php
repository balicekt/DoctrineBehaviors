<?php

namespace Clear01\DoctrineBehaviors\Sortable;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class EntityPosition
{
	/**
	 * @var string
	 * @ORM\Column(type="string", nullable=false)
	 * @ORM\Id()
	 */
	protected $scope;

	/**
	 * @var int
	 * @ORM\Column(type="integer", nullable=false)
	 */
	protected $entityId;

	/**
	 * @var int
	 * @ORM\Column(type="integer", nullable=false)
	 */
	protected $position;

	/**
	 * EntityPosition constructor.
	 * @param string $scope
	 * @param int $entityId
	 * @param int $position
	 */
	public function __construct($scope, $entityId, $position)
	{
		$this->scope = $scope;
		$this->entityId = $entityId;
		$this->position = $position;
	}

	/**
	 * @return string
	 */
	public function getScope(): string
	{
		return $this->scope;
	}

	/**
	 * @return int
	 */
	public function getEntityId(): int
	{
		return $this->entityId;
	}

	/**
	 * @return int
	 */
	public function getPosition(): int
	{
		return $this->position;
	}


}