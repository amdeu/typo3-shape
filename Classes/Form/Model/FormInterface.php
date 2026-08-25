<?php

namespace Amdeu\Shape\Form\Model;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;

interface FormInterface extends ContainerInterface
{
	public function getIdentifier(): int|string;

	public function getName(): string;

	/**
	 * @return LazyRecordCollection<FormPageInterface>|array<FormPageInterface>
	 */
	public function getPages(): LazyRecordCollection|array;

	/**
	 * @return LazyRecordCollection<ModuleConfigurationInterface>|array<ModuleConfigurationInterface>
	 */
	public function getModuleConfigurations(): LazyRecordCollection|array;
}