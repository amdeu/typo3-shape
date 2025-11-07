<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Finisher;

use Amdeu\Shape\Form;

final class BeforeFinisherCreationEvent
{
	public function __construct(
		public readonly Form\FormRuntime $runtime,
		public readonly Form\Model\FinisherConfigurationInterface $finisherConfiguration,
		public string $finisherClassName,
		public array $settings,
	)
	{
	}
}