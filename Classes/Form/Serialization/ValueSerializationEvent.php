<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Serialization;

use Amdeu\Shape\Form;

final class ValueSerializationEvent
{
	public function __construct(
		public readonly Form\FormRuntime          $runtime,
		public readonly Form\Model\FieldInterface $field,
		public readonly mixed                     $value,
		public mixed                              $serializedValue = null,
	)
	{
	}

	public function isPropagationStopped(): bool
	{
		return $this->serializedValue !== null;
	}
}