<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Validator;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;

final class SubsetArrayValidator extends AbstractValidator
{
	protected $supportedOptions = [
		'array' => [[], 'The array to use for validation', 'array', true],
	];

	public function isValid(mixed $value): void
	{
		if (!is_array($this->options['array'])) {
			throw new InvalidValidationOptionsException('Option "array" must be of type array', 1739105404);
		}
		if (array_diff($value, $this->options['array'])) {
			$this->addError(
				$this->translateErrorMessage(
					'validation.error.subset_array',
					'shape',
				),
				1739105405
			);
		}
	}
}
