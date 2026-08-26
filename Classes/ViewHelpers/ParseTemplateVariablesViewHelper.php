<?php

namespace Amdeu\Shape\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use Amdeu\Shape\Utility\TemplateVariableParser;

class ParseTemplateVariablesViewHelper extends AbstractViewHelper
{
	public function initializeArguments(): void
	{
		$this->registerArgument('string', 'string', '', false, '');
		$this->registerArgument('data', 'array', '', true);
		$this->registerArgument('escapeHtml', 'bool', '', false, false);
	}

	public function render(): string
	{
		return TemplateVariableParser::parse($this->arguments['string'], $this->arguments['data'], $this->arguments['escapeHtml']);
	}

	public function getContentArgumentName(): string
	{
		return 'string';
	}
}
