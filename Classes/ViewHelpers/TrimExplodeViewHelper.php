<?php

namespace Amdeu\Shape\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class TrimExplodeViewHelper extends AbstractViewHelper
{
	public function initializeArguments(): void
	{
		$this->registerArgument('string', 'string', 'String to split', true);
		$this->registerArgument('delimiter', 'string', 'Delimiter', false, PHP_EOL);
	}

	public function render(): array
	{
		$string = $this->renderChildren();
		if (!$string) {
			return [];
		}
		return array_map('trim', explode($this->arguments['delimiter'], $string));
	}

	public function getContentArgumentName(): string
	{
		return 'string';
	}
}