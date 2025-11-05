<?php

namespace UBOS\Shape\Form\Model;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;

interface FormInterface extends ContainerInterface
{
	public function getUid(): int;

	public function getName(): string;

	/**
	 * @return LazyRecordCollection<FormPageInterface>|array<FormPageInterface>
	 */
	public function getPages(): LazyRecordCollection|array;

	/**
	 * @return LazyRecordCollection<FinisherConfigurationInterface>|array<FinisherConfigurationInterface>
	 */
	public function getFinisherConfigurations(): LazyRecordCollection|array;
}