<?php

namespace UBOS\Shape\Form\Model;

use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record\SystemProperties;
use TYPO3\CMS\Extbase\Error\Result;

class FieldRecord extends Record implements FieldInterface
{
	protected mixed $sessionValue = null;
	protected bool $conditionResult = true;
	protected ?Result $validationResult = null;
	protected array $runtimeOverrides = [];
	protected ?array $optionSelection = null;

	public function __construct(
		protected readonly RawRecord         $rawRecord,
		protected array                      $properties,
		protected readonly ?SystemProperties $systemProperties = null,
	)
	{
		$this->initializeDefaultValue();
		$this->normalizeHtmlAttributeProperties();
	}

	protected function initializeDefaultValue(): void
	{
		// guarantee default_value exists
		if ($this->has('default_value')) {
			return;
		}
		$this->properties['default_value'] = null;

		// Set default value for fields with options
		// Types that start with 'multi-' are multi-select fields and have an array as default value
		if (!$this->has('field_options')) {
			return;
		}
		if (str_starts_with($this->getType(),'multi-')) {
			$value = [];
			foreach ($this->get('field_options') as $option) {
				if ($option->get('selected')) {
					$value[] = $option->get('value');
				}
			}
			if ($value) {
				$this->properties['default_value'] = $value;
			}
		} else {
			foreach ($this->get('field_options') as $option) {
				if ($option->get('selected')) {
					$this->properties['default_value'] = $option->get('value');
					break;
				}
			}
		}
	}

	const TYPE_DATETIME_FORMATS = [
		'date' => 'Y-m-d',
		'time' => 'H:i',
		'datetime' => 'Y-m-d H:i',
		'datetime-local' => 'Y-m-d\TH:i',
		'week' => 'Y-\WW',
		'month' => 'Y-m',
	];

	/**
	 * Normalize specific properties to strings for HTML attributes
	 *
	 * TCA uses datetime type for editor convenience, but these properties
	 * represent HTML input attributes (min, max, value) which are strings.
	 */
	protected function normalizeHtmlAttributeProperties(): void
	{
		foreach (['default_value', 'min', 'max'] as $key) {
			if ($this->has($key) && $this->properties[$key] instanceof \DateTimeInterface) {
				$format = self::TYPE_DATETIME_FORMATS[$this->getType()] ?? 'Y-m-d H:i:s';
				$this->properties[$key] = $this->properties[$key]->format($format);
			}
		}
	}

	public function get(string $id): mixed
	{
		return $this->runtimeOverrides[$id] ?? parent::get($id);
	}
	public function isFormControl(): bool
	{
		return $this->has('name');
	}
	public function getName(): string
	{
		return $this->properties['name'] ?? '';
	}
	public function getType(): string
	{
		return $this->properties['type'] ?? '';
	}
	public function getValue(): mixed
	{
		return $this->sessionValue ?? $this->get('default_value');
	}
	public function getSessionValue(): mixed
	{
		return $this->sessionValue;
	}
	public function setSessionValue(mixed $value): void
	{
		$this->sessionValue = $value;
		$this->optionSelection = null;
	}
	public function getConditionResult(): bool
	{
		return $this->conditionResult;
	}
	public function setConditionResult(bool $result): void
	{
		$this->conditionResult = $result;
	}
	public function getValidationResult(): ?Result
	{
		return $this->validationResult;
	}
	public function setValidationResult(?Result $result): void
	{
		$this->validationResult = $result;
	}
	public function runtimeOverride(string $key, mixed $value): void
	{
		$this->runtimeOverrides[$key] = $value;
	}

	public function getOptions(): ?array
	{
		if (!$this->has('field_options')) {
			return null;
		}
		return $this->get('field_options');
	}

	public function getOptionValues(): ?array
	{
		if (!$this->has('field_options')) {
			return null;
		}
		$values = [];
		foreach ($this->get('field_options') as $option) {
			$values[] = $option->get('value');
		}
		return $values;
	}

	public function getOptionSelection(): ?array
	{
		if (!$this->has('field_options')) {
			return null;
		}
		if ($this->optionSelection !== null) {
			return $this->optionSelection;
		}
		$selection = [];
		$fieldValue = $this->getValue();
		foreach ($this->get('field_options') as $option) {
			$optionValue = $option->get('value');
			$selection[$optionValue] = is_array($fieldValue) ? in_array($optionValue, $fieldValue) : $optionValue == $fieldValue;
		}
		return $selection;
	}

	public function getOptionGroups(): array
	{
		$groupedOptions = [];
		$groupLabel = '';
		foreach ($this->get('field_options') as $option) {
			if ($option->get('group_label')) {
				$groupLabel = $option->get('group_label');
			}
			if (!isset($groupedOptions[$groupLabel])) {
				$groupedOptions[$groupLabel] = [];
			}
			$groupedOptions[$groupLabel][] = $option;
		}
		return $groupedOptions;
	}
}
