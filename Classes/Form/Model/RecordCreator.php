<?php

namespace Amdeu\Shape\Form\Model;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Domain\Event\RecordCreationEvent;

final class RecordCreator
{
	#[AsEventListener]
	public function __invoke(RecordCreationEvent $event): void
	{
		if ($event->isPropagationStopped()) {
			return;
		}
		switch ($event->getRawRecord()->getMainType()) {
			case 'tx_shape_field':
				$this->setRecord($event, FieldRecord::class);
				break;
			case 'tx_shape_finisher':
				$this->setRecord($event, FinisherConfigurationRecord::class);
				break;
			case 'tx_shape_form_page':
				$this->setRecord($event, FormPageRecord::class);
				break;
			case 'tx_shape_form':
				$this->setRecord($event, FormRecord::class);
				break;
		}
	}

	protected function setRecord(RecordCreationEvent $event, string $className): void
	{
		$event->setRecord(new $className(
			$event->getRawRecord(),
			$event->getProperties(),
			$event->getSystemProperties()
		));
	}
}
