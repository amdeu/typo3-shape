<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Finisher;

use Psr\Http\Message\ResponseInterface;
use Amdeu\Shape\Form;

class FinisherExecutionContext
{
	public function __construct(
		public readonly Form\FormRuntime $runtime,
		public ?ResponseInterface        $response = null,
		public array                     $finishedActionArguments = [],
		protected bool                   $cancelled = false,
	)
	{
	}

	public function cancel(): void
	{
		$this->cancelled = true;
	}
	public function isCancelled(): bool
	{
		return $this->cancelled;
	}
}