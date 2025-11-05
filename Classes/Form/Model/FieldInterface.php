<?php

namespace UBOS\Shape\Form\Model;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Extbase\Error\Result;

interface FieldInterface extends ContainerInterface
{
	public function isFormControl(): bool;

	public function getName(): string;

	public function getType(): string;

	public function getValue(): mixed;

	public function getSessionValue(): mixed;

	public function setSessionValue(mixed $value): void;

	public function getConditionResult(): bool;

	public function setConditionResult(bool $result): void;

	public function getValidationResult(): ?Result;

	public function setValidationResult(?Result $result): void;

	/**
	 * Get field options (for select, radio, checkbox fields, etc.)
	 *
	 * @return LazyRecordCollection<ContainerInterface>|array<ContainerInterface>|null
	 */
	public function getOptions(): LazyRecordCollection|array|null;

	/**
	 * Get option values only
	 *
	 * @return array<string>|null
	 */
	public function getOptionValues(): ?array;

	public function runtimeOverride(string $key, mixed $value): void;
}