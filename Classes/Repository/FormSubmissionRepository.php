<?php

declare(strict_types=1);

namespace Amdeu\Shape\Repository;

class FormSubmissionRepository extends AbstractRecordRepository
{
	public function getTableName(): string
	{
		return 'tx_shape_form_submission';
	}

	public function isUniqueValue(
		string $fieldName,
		mixed $value,
		int $pluginUid = 0,
		int $formUid = 0,
		string $formName = ''
	): bool
	{
		if (!preg_match('/^[a-zA-Z0-9_-]+$/', $fieldName)) {
			throw new \InvalidArgumentException(
				sprintf('Invalid field name "%s" for uniqueness check.', $fieldName),
				1740000001
			);
		}

		$builder = $this->getQueryBuilder();
		$builder
			->count('uid')
			->from($this->getTableName())
			->andWhere(
				$builder->expr()->eq(
					'JSON_UNQUOTE(JSON_EXTRACT(form_values, ' . $builder->createNamedParameter('$."' . $fieldName . '"') . '))',
					$builder->createNamedParameter($value)
				)
			);
		if ($pluginUid) {
			$builder->andWhere($builder->expr()->eq('plugin', $builder->createNamedParameter($pluginUid)));
		}
		if ($formUid) {
			$builder->andWhere($builder->expr()->eq('form', $builder->createNamedParameter($formUid)));
		}
		$count = (int)$builder->executeQuery()->fetchOne();
		return !$count;
	}
}