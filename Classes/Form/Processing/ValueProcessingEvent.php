<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Processing;

use Amdeu\Shape\Form;

/**
 * Event dispatched by @see FieldValueProcessor to allow listeners to process field values.
 */
final class ValueProcessingEvent implements Form\FormEventInterface
{
	public function __construct(
		public readonly Form\FormRuntime          $runtime,
		public readonly Form\Model\FieldInterface $field,
		public readonly mixed                     $value,
		public mixed                              $processedValue = null,
	)
	{
	}

	public function getRuntime(): Form\FormRuntime
	{
		return $this->runtime;
	}

	public function isPropagationStopped(): bool
	{
		return $this->processedValue !== null;
	}
}