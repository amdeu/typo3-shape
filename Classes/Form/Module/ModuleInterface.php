<?php

namespace Amdeu\Shape\Form\Module;

use TYPO3\CMS\Extbase;
use Amdeu\Shape\Form;

interface ModuleInterface
{
	public function configure(
		Form\FormRuntime $runtime,
		?Form\Model\ModuleConfigurationInterface $configuration
	): void;

}