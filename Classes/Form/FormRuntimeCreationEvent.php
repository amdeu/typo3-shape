<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form;

final class FormRuntimeCreationEvent implements FormEventInterface
{
	public function __construct(
		public readonly FormRuntime $runtime,
	)
	{
	}

	public function getRuntime(): FormRuntime
	{
		return $this->runtime;
	}
}