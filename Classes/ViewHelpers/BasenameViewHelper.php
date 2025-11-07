<?php

namespace Amdeu\Shape\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class BasenameViewHelper extends AbstractViewHelper
{
	public function initializeArguments(): void
	{
		$this->registerArgument('path', 'string', '', true);
	}

	public function render(): string
	{
		$path = $this->renderChildren();
		return basename($path);
	}

	public function getContentArgumentName(): string
	{
		return 'path';
	}
}
