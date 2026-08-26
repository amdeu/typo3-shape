<?php

namespace Amdeu\Shape\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Domain\Record;
use Amdeu\Shape\Repository\GenericRepositoryFactory;

class GetRecordViewHelper extends AbstractViewHelper
{
	public function initializeArguments(): void
	{
		$this->registerArgument('uid', 'int|string', '', true);
		$this->registerArgument('table', 'string', '', true);
	}

	public function render(): ?Record
	{
		$repositoryFactory = GeneralUtility::makeInstance(GenericRepositoryFactory::class);
		$repository = $repositoryFactory
			->forTable($this->arguments['table'])
			->reset();
		return $repository->findByUid((int)$this->arguments['uid']);
	}
}
