<?php

namespace Amdeu\Shape\Form\Validation;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core;
use TYPO3\CMS\Extbase\Validation\Validator as ExtbaseValidator;
use Amdeu\Shape\Form;
use Amdeu\Shape\Form\Validator;

final class ValueValidationConfigurator
{
	#[AsEventListener]
	public function __invoke(ValueValidationEvent $event): void
	{
		if ($event->isPropagationStopped()) {
			return;
		}
		// add validators based on field properties
		$this->addRequiredValidator($event);
		$this->addPatternValidator($event);
		$this->addEmailValidator($event);
		$this->addMaxLengthValidator($event);
		$this->addUrlValidator($event);
		$this->addNumberRangeValidator($event);
		$this->addOptionValidators($event);
		$this->addConfirmInputValidator($event);
		$this->addDateTimeValidator($event);
		$this->addCountValidator($event);
		$this->addFileValidator($event);
	}

	protected function addRequiredValidator(ValueValidationEvent $event): void
	{
		$field = $event->field;
		if ($field->has('required') && $field->get('required') && $field->getConditionResult()) {
			$event->addValidator($this->makeValidator(
				ExtbaseValidator\NotEmptyValidator::class
			));
		}
	}

	protected function addPatternValidator(ValueValidationEvent $event): void
	{
		$field = $event->field;
		if ($field->has('pattern') && $field->get('pattern') && $event->value) {
			$event->addValidator($this->makeValidator(
				Validator\HTMLPatternValidator::class,
				['pattern' => $field->get('pattern')]
			));
		}
	}

	protected function addEmailValidator(ValueValidationEvent $event): void
	{
		$field = $event->field;
		if ($field->getType() === 'email' && $event->value) {
			$event->addValidator($this->makeValidator(
				ExtbaseValidator\EmailAddressValidator::class
			));
		}
	}

	protected function addMaxLengthValidator(ValueValidationEvent $event): void
	{
		$field = $event->field;
		if ($field->has('maxlength') && $field->get('maxlength') && $event->value) {
			$event->addValidator($this->makeValidator(
				ExtbaseValidator\StringLengthValidator::class,
				['maximum' => $field->get('maxlength')],
			));
		}
	}

	protected function addUrlValidator(ValueValidationEvent $event): void
	{
		$field = $event->field;
		if ($field->getType() === 'url' && $event->value) {
			$event->addValidator($this->makeValidator(
				ExtbaseValidator\UrlValidator::class
			));
		}
	}

	protected function addNumberRangeValidator(ValueValidationEvent $event): void
	{
		$field = $event->field;
		if (in_array($field->getType(), ['number','range']) && $event->value !== null) {
			$event->addValidator($this->makeValidator(
				Validator\MultipleOfInRangeValidator::class,
				[
					'step' => $field->get('step') ?? 1,
					'offset' => $field->get('min') ?? $field->get('default_value') ?? 0,
					'minimum' => $field->get('min') ?? null,
					'maximum' => $field->get('max') ?? null
				]
			));
		}
	}

	protected function addOptionValidators(ValueValidationEvent $event): void
	{
		$field = $event->field;
		$optionValues = $field->getOptionValues();
		if (!$optionValues || !$event->value) {
			return;
		}
		$type = $field->getType();
		if (in_array($type, ['select','checkbox','radio'])) {
			$event->addValidator($this->makeValidator(
				Validator\InArrayValidator::class,
				['array' => $optionValues]
			));
		}
		if (in_array($type, ['multi-select','multi-checkbox'])) {
			$event->addValidator($this->makeValidator(
				Validator\SubsetArrayValidator::class,
				['array' => $optionValues]
			));
		}
	}

	protected function addConfirmInputValidator(ValueValidationEvent $event): void
	{
		$field = $event->field;
		if ($field->has('confirm_input') && $field->get('confirm_input') && $event->value && isset($event->runtime->session->values[$field->get('name').'__CONFIRM'])) {
			$event->addValidator($this->makeValidator(
				Validator\EqualValidator::class,
				['value' => $event->runtime->session->values[$field->get('name').'__CONFIRM'] ?? null]
			));
		}
	}

	protected function addDateTimeValidator(ValueValidationEvent $event): void
	{
		$field = $event->field;
		$type = $field->getType();
		if (in_array($type, ['date','datetime-local','time','week','month']) && $event->value) {
			$event->addValidator($this->makeValidator(
				Validator\DateTimeRangeValidator::class,
				[
					'minimum' => $field->get('min') ?? '',
					'maximum' => $field->get('max') ?? '',
					'format' => Form\Model\FieldRecord::TYPE_DATETIME_FORMATS[$type]
				]
			));
		}
	}

	protected function addCountValidator(ValueValidationEvent $event): void
	{
		$field = $event->field;
		if ($field->getType() !== 'file' && is_array($event->value) && ($field->has('min') || $field->has('max'))) {
			$event->addValidator($this->makeValidator(
				Validator\CountValidator::class,
				[
					'minimum' => $field->get('min') ?? null,
					'maximum' => $field->get('max') ?? null,
				]
			));
		}
	}

	protected function addFileValidator(ValueValidationEvent $event): void
	{
		$field = $event->field;
		$value = $event->value;
		if ($field->getType() === 'file' && $value) {
			if (!is_array($value)) {
				$value = [$value];
			}
			$fileValidator = Core\Utility\GeneralUtility::makeInstance(Validator\ArrayValuesConjunctionValidator::class);
			if (is_string(reset($value))) {
				$fileValidator->addValidator($this->makeValidator(
					Validator\CombinedFileIdentifierValidator::class,
				));
			} else {
				$fileValidator->addValidator($this->makeValidator(
					Validator\FileUploadValidator::class
				));
				if ($field->get('accept')) {
					$fileValidator->addValidator($this->makeValidator(
						Validator\HTMLAcceptValidator::class,
						['accept' => $field->get('accept')],
					));
				}
				if ($field->get('min') || $field->get('max')) {
					$fileValidator->addValidator($this->makeValidator(
						ExtbaseValidator\FileSizeValidator::class,
						[
							'minimum' => ($field->get('min') ?? '0') . 'K',
							'maximum' => $field->get('max') ? ($field->get('max') . 'K') : (PHP_INT_MAX . 'B')
						]
					));
				}
			}
			$event->addValidator($fileValidator);
			$event->value = $value;
		}
	}

	protected function makeValidator(string $className, array $options = []): ExtbaseValidator\ValidatorInterface
	{
		$validator = Core\Utility\GeneralUtility::makeInstance($className);
		$validator->setOptions($options);
		return $validator;
	}
}
