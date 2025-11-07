<?php

declare(strict_types=1);

namespace Amdeu\Shape\Form\Condition;

use TYPO3\CMS\Core\ExpressionLanguage\AbstractProvider;

class ConditionProvider extends AbstractProvider
{
    public function __construct()
    {
        $this->expressionLanguageProviders = [
            ConditionFunctionsProvider::class,
        ];
    }
}
