<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form;

use Psr\Http\Message\ResponseInterface;

final class FormFinishEvent
{
	public function __construct(
		public readonly FormRuntime	$runtime,
		public ?ResponseInterface 	$response = null,
		public array				$finishedActionArguments = [],
		protected bool              $cancelled = false,
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