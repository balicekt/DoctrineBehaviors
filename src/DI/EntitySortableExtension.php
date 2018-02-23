<?php
declare(strict_types = 1);

namespace Clear01\DoctrineBehaviors\DI;

use Clear01\DoctrineBehaviors\EntitySortable\EntitySortingService;
use Kdyby\Doctrine\DI\IEntityProvider;
use Nette\DI\CompilerExtension;

class EntitySortableExtension extends CompilerExtension implements IEntityProvider
{
	public function beforeCompile()
	{
		parent::beforeCompile();
		$this->getContainerBuilder()->addDefinition($this->prefix('entitySortingService'))
			->setType(EntitySortingService::class);
	}


	/**
	 * Returns associative array of Namespace => mapping definition
	 *
	 * @return array
	 */
	function getEntityMappings()
	{
		return ['Clear01\DoctrineBehaviors\EntitySortable' => dirname(__DIR__) . '/EntitySortable'];
	}
}