<?php

namespace Amdeu\Shape\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use Amdeu\Shape\Utility\TemplateVariableParser;

class ParseTemplateVariablesViewHelper extends AbstractViewHelper
{
	/**
	 * Interpolated values are HTML-escaped by the parser unless escapeHtml is explicitly disabled
	 * (literal template text always passes through), so Fluid must not escape the result again.
	 */
	protected $escapeOutput = false;

	public function initializeArguments(): void
	{
		$this->registerArgument('string', 'string', '', false, '');
		$this->registerArgument('data', 'array', '', true);
		$this->registerArgument('escapeHtml', 'bool', 'Escape HTML in the interpolated values (disable only for trusted data)', false, true);
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
