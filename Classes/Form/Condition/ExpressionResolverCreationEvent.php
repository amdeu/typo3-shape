<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Condition;

use Amdeu\Shape\Form;

final class ExpressionResolverCreationEvent implements Form\FormEventInterface
{
	public function __construct(
		public readonly Form\FormRuntime $runtime,
		protected array $variables
	) {}

	public function getRuntime(): Form\FormRuntime
	{
		return $this->runtime;
	}

	public function getVariables(): array
	{
		return $this->variables;
	}

	public function addVariables(array $variables): void
	{
		$this->variables = array_merge($this->variables, $variables);
	}
}