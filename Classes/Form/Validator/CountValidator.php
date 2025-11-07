<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Validator;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

final class CountValidator extends AbstractValidator
{
    protected $supportedOptions = [
        'minimum' => [null, 'The minimum count to accept', 'integer'],
        'maximum' => [null, 'The maximum count to accept', 'integer'],
    ];

    /**
     * The given value is valid if it is an array or \Countable that contains the specified amount of elements.
     */
    public function isValid(mixed $value): void
    {
        if (!is_array($value) && !($value instanceof \Countable)) {
            $this->addError(
                $this->translateErrorMessage(
                    'validation.error.count.countable',
                    'shape'
                ),
				1739395512
            );
            return;
        }
		$count = count($value);
		$minimum = $this->options['minimum'] !== null ? (int)$this->options['minimum'] : null;
		$maximum = $this->options['maximum'] !== null ? (int)$this->options['maximum'] : null;

		if ($minimum !== null && $count < $minimum) {
			$this->addError(
				$this->translateErrorMessage('validation.error.count.minimum', 'shape', [$minimum]),
				1739395513,
				[$minimum]
			);
		}

		if ($maximum !== null && $count > $maximum) {
			$this->addError(
				$this->translateErrorMessage('validation.error.count.maximum', 'shape', [$maximum]),
				1739395514,
				[$maximum]
			);
		}
    }
}
