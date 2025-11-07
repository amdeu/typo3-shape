<?php

namespace Amdeu\Shape\Form\Model;

use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Domain\Record;

class FormRecord extends Record implements FormInterface
{
	public function getName(): string
	{
		return $this->properties['name'] ?? '';
	}

	/**
	 * @return LazyRecordCollection<FormPageInterface>|array<FormPageInterface>
	 */
	public function getPages(): LazyRecordCollection|array
	{
		return $this->get('pages');
	}

	/**
	 * @return LazyRecordCollection<FinisherConfigurationInterface>|array<FinisherConfigurationInterface>
	 */
	public function getFinisherConfigurations(): LazyRecordCollection|array
	{
		return $this->get('finishers');
	}
}