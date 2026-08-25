<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Module;

use TYPO3\CMS\Core\ExpressionLanguage\Resolver;
use Amdeu\Shape\Form;

final class ModuleConditionResolutionEvent implements Form\FormEventInterface
{
	public function __construct(
		public readonly Form\FormRuntime          $runtime,
		public readonly Form\Model\ModuleConfigurationInterface $moduleConfiguration,
		public readonly Resolver                  $resolver,
		public ?bool                              $result = null,
	) {}

	public function getRuntime(): Form\FormRuntime
	{
		return $this->runtime;
	}

	public function isPropagationStopped(): bool
	{
		return $this->result !== null;
	}
}