<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form;

use Psr\Http\Message\ResponseInterface;

final class FormFinishEvent implements FormEventInterface, Condition\HasConditionVariablesInterface
{
	public string $finishedTemplate = '';
	public array $finishedVariables = [];

	public function __construct(
		public readonly FormRuntime	$runtime,
		public ?ResponseInterface 	$response = null,
		protected bool              $propagationStopped = false,
		protected array             $conditionVariables = [],
	)
	{
	}

	public function addFinishedVariables(array $variables): void
	{
		$this->finishedVariables = array_merge($this->finishedVariables, $variables);
	}

	public function getRuntime(): FormRuntime
	{
		return $this->runtime;
	}

	public function getConditionVariables(): array
	{
		return $this->conditionVariables;
	}

	public function stopPropagation(): void
	{
		$this->propagationStopped = true;
	}

	public function isPropagationStopped(): bool
	{
		return $this->propagationStopped;
	}
}