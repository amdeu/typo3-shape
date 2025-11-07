<?php

namespace Amdeu\Shape\Form\Finisher;

use TYPO3\CMS\Extbase;

interface FinisherInterface
{
	public function setSettings(array $settings): void;
	public function validate(): Extbase\Error\Result;
	public function execute(FinisherExecutionContext $context): void;

}