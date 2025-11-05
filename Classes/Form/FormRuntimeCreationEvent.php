<?php

declare(strict_types=1);

namespace UBOS\Shape\Form;

final class FormRuntimeCreationEvent
{
	public function __construct(
		public readonly FormRuntime $runtime,
	)
	{
	}
}