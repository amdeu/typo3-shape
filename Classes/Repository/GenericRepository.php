<?php

declare(strict_types=1);

namespace Amdeu\Shape\Repository;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Generic repository for tables without a dedicated repository class.
 *
 * USAGE GUIDELINES:
 * - RECOMMENDED: Use via GenericRepositoryFactory for automatic instance management
 * - Each instance is tied to ONE table (enforced by readonly property after forTable() call)
 * - Create new instances for different tables - do NOT attempt to reuse instances
 * - forTable() can only be called once per instance (throws exception on subsequent calls)
 *
 * All table structure information is read from TCA automatically.
 *
 * Example usage:
 * ```php
 * // Via factory (recommended - handles instance caching):
 * $items = $this->repositoryFactory
 *     ->forTable('tx_myext_domain_model_item')
 *     ->findAll();
 *
 * // Manual instantiation (for one-off usage):
 * $repo = GeneralUtility::makeInstance(GenericRepository::class);
 * $repo->forTable('tx_myext_domain_model_item');
 * $items = $repo->setLanguageUid(0)->findAll();
 * ```
 */
#[Autoconfigure(public: true, shared: false)]
class GenericRepository extends AbstractRecordRepository
{
	private ?string $tableName = null;

	public function getTableName(): string
	{
		if ($this->tableName === null) {
			throw new \RuntimeException(
				'Table name not set. Call forTable() before using this repository.'
			);
		}
		return $this->tableName;
	}

	/**
	 * Configure the repository for a specific table.
	 * Can only be called once per instance - table cannot be changed after initialization.
	 *
	 * @param string $tableName The database table name
	 * @return self Returns itself for fluent interface
	 * @throws \RuntimeException If table name is already set
	 */
	public function forTable(string $tableName): self
	{
		if ($this->tableName !== null) {
			throw new \RuntimeException(
				sprintf(
					'Table name already set to "%s". Cannot change to "%s". ' .
					'Create a new repository instance for different tables.',
					$this->tableName,
					$tableName
				)
			);
		}

		$this->tableName = $tableName;
		return $this;
	}
}