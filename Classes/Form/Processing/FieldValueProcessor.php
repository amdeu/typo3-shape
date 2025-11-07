<?php

namespace Amdeu\Shape\Form\Processing;

use Psr\EventDispatcher\EventDispatcherInterface;
use Amdeu\Shape\Form;

class FieldValueProcessor
{
	public function __construct(
		protected EventDispatcherInterface $eventDispatcher,
	)
	{
	}

	public function process(
		Form\FormRuntime $runtime,
		Form\Model\FieldInterface $field,
		mixed $value
	): mixed
	{
		if (!$field->isFormControl()) {
			return $value;
		}
		$event = new ValueProcessingEvent($runtime, $field, $value);
		$this->eventDispatcher->dispatch($event);
		if ($event->isPropagationStopped()) {
			return $event->processedValue;
		}
		return $value;
	}

}