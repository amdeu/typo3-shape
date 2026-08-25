<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Serialization;

use Amdeu\Shape\Form;

final class ValueSerializationEvent implements Form\FormEventInterface
{
	public function __construct(
		public readonly Form\FormRuntime          $runtime,
		public readonly Form\Model\FieldInterface $field,
		public readonly mixed                     $value,
		public mixed                              $serializedValue = null,
	)
	{
	}

	public function getRuntime(): Form\FormRuntime
	{
		return $this->runtime;
	}

	public function isPropagationStopped(): bool
	{
		return $this->serializedValue !== null;
	}
}