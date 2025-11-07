<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Validator;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Core;

final class CombinedFileIdentifierValidator extends AbstractValidator
{
	protected $supportedOptions = [
	];

	public function __construct(
		protected Core\Resource\ResourceFactory $resourceFactory
	) {}

	public function isValid(mixed $value): void
	{
		try {
			$file = $this->resourceFactory->getFileObjectFromCombinedIdentifier($value);
			if (!$file || !$file->exists()) {
				$this->addDefaultError();
			}
		} catch (\Exception $e) {
			$this->addDefaultError();
		}
	}

	public function addDefaultError(): void
	{
		$this->addError(
			$this->translateErrorMessage(
				'validation.error.combined_file_identifier',
				'shape',
			),
			1739105229
		);
	}
}
