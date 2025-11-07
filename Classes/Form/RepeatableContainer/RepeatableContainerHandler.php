<?php

namespace Amdeu\Shape\Form\RepeatableContainer;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Domain\Event\RecordCreationEvent;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use Amdeu\Shape\Form;

final class RepeatableContainerHandler
{
	public function __construct(
		protected EventDispatcher                                  $eventDispatcher,
		protected readonly Form\Validation\FieldValueValidator     $fieldValueValidator,
		protected readonly Form\Processing\FieldValueProcessor     $fieldValueProcessor,
		protected readonly Form\Serialization\FieldValueSerializer $fieldValueSerializer,
		protected readonly Form\Condition\FieldConditionResolver   $fieldConditionResolver,
	) {}

	#[AsEventListener(before: 'Amdeu\Shape\EventListener\RecordCreator')]
	public function createRecord(RecordCreationEvent $event): void
	{
		if ($event->isPropagationStopped()) {
			return;
		}
		if ($event->getRawRecord()->getMainType() === 'tx_shape_field'
			&& $event->getRawRecord()->get('type') === 'repeatable-container') {
			$event->setRecord(new RepeatableContainerRecord(
				$event->getRawRecord(),
				$event->getProperties(),
				$event->getSystemProperties()
			));
		}
	}

	#[AsEventListener]
	public function resolveFieldCondition(Form\Condition\FieldConditionResolutionEvent $event): void
	{
		$field = $event->field;
		if (!($field instanceof RepeatableContainerRecord)) {
			return;
		}
		$event->result = true;
		if ($field->get('display_condition')) {
			$event->result = $event->resolver->evaluate($field->get('display_condition'));
		}
		if (!$event->result) {
			return;
		}
		foreach ($field->getCreatedFieldsets() as $index => $fields) {
			foreach ($fields as $childField) {
				$childField->set('display_condition', str_replace('__INDEX', $index, $childField->get('display_condition')));
				$childField->setConditionResult($this->fieldConditionResolver->evaluate($event->runtime, $childField, $event->resolver));
			}
		}
	}

	#[AsEventListener(after: 'Amdeu\Shape\EventListener\ValueValidationConfigurator')]
	public function validateValue(Form\Validation\ValueValidationEvent $event): void
	{
		$field = $event->field;
		if (!($field instanceof RepeatableContainerRecord)) {
			return;
		}
		$result = $event->validator->validate($event->value);
		if (!$event->value) {
			$event->result = $result;
			return;
		}
		foreach ($field->getCreatedFieldsets() as $index => $fields) {
			$result->forProperty($index);
			foreach ($fields as $childField) {
				$name = $childField->getName();
				$value = $event->value[$index][$name] ?? $event->value[$index][$name . '__PROXY'] ?? null;
				$childField->setValidationResult($this->fieldValueValidator->validate($event->runtime, $childField, $value));
				$result->forProperty($index)->forProperty($name)->merge($childField->getValidationResult());
			}
		}
		$event->result = $result;
	}

	#[AsEventListener(before: 'Amdeu\Shape\EventListener\ValueSerializationHandler')]
	public function serializeValue(Form\Serialization\ValueSerializationEvent $event): void
	{
		$field = $event->field;
		if (!($field instanceof RepeatableContainerRecord)) {
			return;
		}
		$serializedValue = [];
		if (!$event->value) {
			$event->serializedValue = $serializedValue;
			return;
		}
		foreach ($field->getCreatedFieldsets() as $index => $fields) {
			$serializedValue[$index] = [];
			foreach ($fields as $childField) {
				$name = $childField->getName();
				$value = $event->value[$index][$name] ?? $event->value[$index][$name . '__PROXY'] ?? null;
				$serializedChildValue = $this->fieldValueSerializer->serialize($event->runtime, $childField, $value);
				$childField->setSessionValue($serializedChildValue);
				$serializedValue[$index][$name] = $serializedChildValue;
				if (isset($event->value[$index][$name.'__CONFIRM'])) {
					$serializedValue[$index][$name.'__CONFIRM'] = $serializedChildValue;
				}
			}
		}
		$event->serializedValue = $serializedValue;
	}

	#[AsEventListener(before: 'Amdeu\Shape\EventListener\ValueProcessingHandler')]
	public function processValue(Form\Processing\ValueProcessingEvent $event): void
	{
		$field = $event->field;
		if (!($field instanceof RepeatableContainerRecord)) {
			return;
		}
		$processedValue = [];
		if (!$event->value) {
			$event->processedValue = $processedValue;
			return;
		}
		foreach ($field->getCreatedFieldsets() as $index => $fields) {
			$processedValue[$index] = [];
			foreach ($fields as $childField) {
				$name = $childField->getName();
				$value = $event->value[$index][$name] ?? $event->value[$index][$name . '__PROXY'] ?? null;
				$processedChildValue = $this->fieldValueProcessor->process($event->runtime, $childField, $value);
				$childField->setSessionValue($processedChildValue);
				$processedValue[$index][$name] = $processedChildValue;
				if (isset($event->value[$index][$name.'__CONFIRM'])) {
					$processedValue[$index][$name.'__CONFIRM'] = $processedChildValue;
				}
			}
		}
		$event->processedValue = $processedValue;
	}
}
