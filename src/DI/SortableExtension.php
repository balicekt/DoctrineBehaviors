<?php
declare(strict_types = 1);

namespace Clear01\DoctrineBehaviors\DI;

use Clear01\DoctrineBehaviors\Sortable\EntitySortingHandler;
use Kdyby\Events\DI\EventsExtension;
use Knp\DoctrineBehaviors\Model\Sortable\Sortable;
use Knp\DoctrineBehaviors\ORM\Sortable\SortableSubscriber;
use Nette\Utils\AssertionException;
use Nette\Utils\Validators;
use Zenify\DoctrineBehaviors\DI\AbstractBehaviorExtension;

final class SortableExtension extends AbstractBehaviorExtension
{
	/**
	 * @var array
	 */
	private $default = [
		'isRecursive' => TRUE,
		'trait' => Sortable::class
	];


	/**
	 * @throws AssertionException
	 */
	public function loadConfiguration()
	{

		$config = $this->getConfig($this->default);
		$this->validateConfigTypes($config);
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('listener'))
		        ->setFactory(SortableSubscriber::class, [
			        '@' . $this->getClassAnalyzer()->getClass(),
			        $config['isRecursive'],
			        $config['trait']
		        ])
		        ->setAutowired(FALSE)
		        ->addTag(EventsExtension::TAG_SUBSCRIBER);
	}

	/**
	 * @throws AssertionException
	 */
	private function validateConfigTypes(array $config)
	{
		Validators::assertField($config, 'isRecursive', 'bool');
		Validators::assertField($config, 'trait', 'type');
	}
}