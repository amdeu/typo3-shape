<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Validator;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

final class MultipleOfInRangeValidator extends AbstractValidator
{
    protected $supportedOptions = [
        'step' => [1, 'The number value should be a multiple of', 'float'],
		'offset' => [0, 'The number that is subtracted from value before doing multiple of validation', 'float'],
		'minimum' => [null, 'The minimum value to accept', 'float'],
		'maximum' => [null, 'The maximum value to accept', 'float'],
		'precision' => [12, 'The number of decimal places to consider', 'integer'],
    ];

	/**
	 * The given value is valid if it is a number in the specified range and also a multiple of step after subtracting offset.
	 */
    public function isValid(mixed $value): void
    {
        if (!is_numeric($value)) {
			$this->addError(
				$this->translateErrorMessage(
					'validation.error.multiple_of_in_range.numeric',
					'shape',
				),
				1739395507
			);
			return;
        }
		$minimum = $this->options['minimum'];
		if ($minimum !== null && $value < $minimum) {
			$this->addError(
				$this->translateErrorMessage(
					'validation.error.multiple_of_in_range.minimum',
					'shape',
					[$minimum]
				),
				1739395508,
				[$minimum]
			);
			return;
		}
		$maximum = $this->options['maximum'];
		if ($maximum !== null && $value > $maximum) {
			$this->addError(
				$this->translateErrorMessage(
					'validation.error.multiple_of_in_range.maximum',
					'shape',
					[$maximum]
				),
				1739395509,
				[$maximum]
			);
			return;
		}
		if ($this->options['step'] == 0 || $this->options['step'] == null) {
			return;
		}
		$precision = $this->options['precision'];
		$offset = $this->options['offset'];
		$step = round((float)$this->options['step'], $precision);
		$diff = round($value - $offset, $precision);
		$allowance = pow(10, -$precision);
		if (fmod($diff, $step) > $allowance) {
			$nearestValues = [
				$offset + (int)($diff / $step) * $step,
				$offset + (int)($diff / $step + 1) * $step,
			];
			if ($minimum !== null && $nearestValues[0] < $minimum) {
				$nearestValues[0] = $minimum;
			}
			if ($maximum !== null && $nearestValues[1] > $maximum) {
				unset($nearestValues[1]);
			}
			if (count($nearestValues) === 1) {
				$this->addError(
					$this->translateErrorMessage(
						'validation.error.multiple_of_in_range.nearest_single',
						'shape',
						$nearestValues
					),
					1739395510,
					$nearestValues
				);
				return;
			}
			$this->addError(
				$this->translateErrorMessage(
					'validation.error.multiple_of_in_range.nearest',
					'shape',
					$nearestValues
				),
				1739395511,
				$nearestValues
			);
		}
    }
}
