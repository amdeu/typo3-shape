<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Condition;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Amdeu\Shape\Enum;

class ConditionFunctionsProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            $this->getFormValueFunction(),
			$this->getFormValueFunction('value'),
			$this->getIsConsentApprovedFunction(),
			$this->getIsConsentDismissedFunction(),
			$this->getIsBeforeConsentFunction(),
        ];
    }

    protected function getFormValueFunction(string $name = 'getFormValue'): ExpressionFunction
    {
        return new ExpressionFunction(
            $name,
            static fn() => null, // Not implemented, we only use the evaluator
            static function ($arguments, $field, $default = null) {
                return $arguments['formValues'][$field] ?? $default;
            }
        );
    }

	protected function getIsConsentApprovedFunction(): ExpressionFunction
	{
		return new ExpressionFunction(
			'isConsentApproved',
			static fn() => null, // Not implemented, we only use the evaluator
			static function ($arguments, $default = '') {
				return ($arguments['consentStatus'] ?? '') === Enum\ConsentStatus::Approved->value;
			}
		);
	}

	protected function getIsConsentDismissedFunction(): ExpressionFunction
	{
		return new ExpressionFunction(
			'isConsentDismissed',
			static fn() => null, // Not implemented, we only use the evaluator
			static function ($arguments, $default = '') {
				return ($arguments['consentStatus'] ?? '') === Enum\ConsentStatus::Dismissed->value;
			}
		);
	}

	protected function getIsBeforeConsentFunction(): ExpressionFunction
	{
		return new ExpressionFunction(
			'isBeforeConsent',
			static fn() => null, // Not implemented, we only use the evaluator
			static function ($arguments, $default = '') {
				return ($arguments['consentStatus'] ?? Enum\ConsentStatus::Pending->value) === Enum\ConsentStatus::Pending->value;
			}
		);
	}

}
