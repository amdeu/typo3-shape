<?php

declare(strict_types=1);

namespace Amdeu\Shape\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Factory for creating GenericRepository instances.
 *
 * Maintains one repository instance per table for optimal cache reuse.
 * Recommended way to work with generic repositories.
 *
 * Usage:
 * ```php
 * public function __construct(
 *     private readonly GenericRepositoryFactory $repositoryFactory,
 * ) {}
 *
 * public function myMethod(): void
 * {
 *     $items = $this->repositoryFactory
 *         ->forTable('tx_myext_domain_model_item')
 *         ->findAll();
 * }
 * ```
 */
#[Autoconfigure(public: true)]
class GenericRepositoryFactory
{
	/**
	 * Cache of repository instances per table.
	 * @var array<string, GenericRepository>
	 */
	private array $instances = [];

	/**
	 * Get a GenericRepository instance for the specified table.
	 * Returns the same instance for repeated calls with the same table name.
	 *
	 * @param string $tableName The database table name
	 * @return GenericRepository Repository instance for the table
	 */
	public function forTable(string $tableName): GenericRepository
	{
		if (!isset($this->instances[$tableName])) {
			$repository = GeneralUtility::makeInstance(GenericRepository::class);
			$repository->forTable($tableName);
			$this->instances[$tableName] = $repository;
		}

		return $this->instances[$tableName];
	}

	/**
	 * Clear all cached repository instances.
	 * Useful for testing or when you need fresh instances.
	 */
	public function clearInstances(): void
	{
		$this->instances = [];
	}
}