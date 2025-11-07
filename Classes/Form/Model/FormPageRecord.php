<?php

namespace Amdeu\Shape\Form\Model;

use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Domain\Record;

class FormPageRecord extends Record implements FormPageInterface
{
	public function getType(): string
	{
		return $this->properties['type'] ?? '';
	}

	/**
	 * @return LazyRecordCollection<FieldInterface>|array<FieldInterface>
	 */
	public function getFields(): LazyRecordCollection|array
	{
		return $this->get('fields');
	}
}