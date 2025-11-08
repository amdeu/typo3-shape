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

	/**
	 * Processes a field value by dispatching a @see ValueProcessingEvent
	 *
	 * @param Form\FormRuntime $runtime The form runtime context.
	 * @param Form\Model\FieldInterface $field The field being processed.
	 * @param mixed $value The original value of the field.
	 * @return mixed The processed value, or the original value if not modified.
	 */
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