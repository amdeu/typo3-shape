<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\SpamProtection;

use Amdeu\Shape\Form;

final class SpamAnalysisEvent
{
	public function __construct(
		public readonly Form\FormRuntime $runtime,
		public array                     $spamReasons = [],
	)
	{
	}
}