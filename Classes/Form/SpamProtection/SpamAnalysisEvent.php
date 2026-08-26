<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\SpamProtection;

use Amdeu\Shape\Form;

final class SpamAnalysisEvent implements Form\FormEventInterface
{
	public function __construct(
		public readonly Form\FormRuntime $runtime,
		public array                     $spamReasons = [],
	)
	{
	}

	public function getRuntime(): Form\FormRuntime
	{
		return $this->runtime;
	}
}