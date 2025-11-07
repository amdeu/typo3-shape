<?php

namespace Amdeu\Shape\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class IsStringViewHelper extends AbstractViewHelper
{
	public function initializeArguments(): void
	{
		$this->registerArgument('variable', 'mixed', '', true);
	}

	public function render(): bool
	{
		$var = $this->renderChildren();
		return is_string($var);
	}

	public function getContentArgumentName(): string
	{
		return 'variable';
	}
}
