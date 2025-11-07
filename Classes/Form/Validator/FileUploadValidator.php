<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Validator;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Core;

final class FileUploadValidator extends AbstractValidator
{
	protected $supportedOptions = [
	];

	public function isValid(mixed $value): void
	{
		if (!$value instanceof Core\Http\UploadedFile) {
			$this->addError(
				$this->translateErrorMessage(
					'validation.error.file_upload',
					'shape',
				),
				1739395516,
			);
		}
	}
}
