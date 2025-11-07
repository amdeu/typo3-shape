<?php

namespace Amdeu\Shape\Form\Model;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;

interface FormPageInterface extends ContainerInterface
{
	public function getType(): string;

	/**
	 * @return LazyRecordCollection<FieldInterface>|array<FieldInterface>
	 */
	public function getFields(): LazyRecordCollection|array;
}