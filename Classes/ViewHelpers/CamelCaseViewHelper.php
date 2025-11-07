<?php

namespace Amdeu\Shape\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class CamelCaseViewHelper extends AbstractViewHelper
{
	public function initializeArguments(): void
	{
		$this->registerArgument('value', 'string', 'String to convert', true);
	}

	public function render(): string
	{
		$value = $this->renderChildren();
		if (!$value) {
			return '';
		}
		return ucfirst(str_replace('-', '', ucwords($value, '-')));
	}

	public function getContentArgumentName(): string
	{
		return 'value';
	}
}