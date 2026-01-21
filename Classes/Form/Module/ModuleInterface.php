<?php

namespace Amdeu\Shape\Form\Module;

use TYPO3\CMS\Extbase;

interface ModuleInterface
{
	public function validate(): Extbase\Error\Result;
}