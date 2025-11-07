<?php

declare(strict_types=1);

namespace Amdeu\Shape\Repository;

use Psr\Log;
use TYPO3\CMS\Core;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Abstract base repository for Record-based domain objects.
 *
 * Provides an API similar to Extbase repositories but optimized for the Record system.
 * Uses TYPO3's Restriction system and respects TCA configuration automatically.
 *
 * IMPORTANT - Table Name Immutability:
 * Each repository instance MUST maintain a single, unchanging table name throughout its lifetime.
 * - Specialized repositories: Return a constant string in getTableName()
 * - GenericRepository: Set once via forTable() (enforced by readonly property)
 *
 * Runtime cache keys use only UIDs, assuming the table never changes per instance.
 * If you create a custom repository that violates this assumption, you MUST manually
 * clear the cache when changing tables using clearRuntimeCache().
 *
 * Performance Note:
 * This repository uses a two-level caching strategy:
 * 1. Record cache: Avoids expensive RecordFactory calls for already-instantiated Records
 * 2. Allowed UIDs cache: Avoids database queries for repeated findByUid() calls
 * Both caches are per-request only and cleared on update/remove operations.
 */
#[Autoconfigure(public: true)]
abstract class AbstractRecordRepository implements Log\LoggerAwareInterface
{
	use Log\LoggerAwareTrait;

	protected ?Core\Database\ConnectionPool $connectionPool = null;
	protected ?Core\Domain\RecordFactory $recordFactory = null;
	protected ?Context $context = null;

	// Runtime cache (per request) - Identity Map pattern
	// Stores Record objects by their UID only (table doesn't change per instance)
	protected array $recordCache = [];

	// Optional: Cache which UIDs are "allowed" for specific query settings
	// This avoids DB queries in findByUid when we already know if a UID passes restrictions
	// Stores the ACTUAL UID returned (important for language-aware lookups)
	protected array $allowedUidsCache = [];

	protected bool $enableRuntimeCache = true;

	// Query settings (similar to Extbase QuerySettings)
	protected bool $respectStoragePage = false;
	protected int $storagePid = 0;
	protected bool $respectSysLanguage = true;
	protected ?int $languageUid = null; // null = use from context
	protected bool $ignoreEnableFields = false;
	protected bool $includeDeleted = false;
	protected bool $returnRawQueryResult = false;

	public function injectConnectionPool(Core\Database\ConnectionPool $connectionPool): void
	{
		$this->connectionPool = $connectionPool;
	}

	public function injectRecordFactory(Core\Domain\RecordFactory $recordFactory): void
	{
		$this->recordFactory = $recordFactory;
	}

	public function injectContext(Context $context): void
	{
		$this->context = $context;
	}

	abstract public function getTableName(): string;

	// ========== Configuration Methods (Extbase-style) ==========

	public function setRespectStoragePage(bool $respectStoragePage): self
	{
		$this->respectStoragePage = $respectStoragePage;
		return $this;
	}

	public function setStoragePid(int $storagePid): self
	{
		$this->storagePid = $storagePid;
		$this->respectStoragePage = true;
		return $this;
	}

	public function setRespectSysLanguage(bool $respectSysLanguage): self
	{
		$this->respectSysLanguage = $respectSysLanguage;
		return $this;
	}

	public function setLanguageUid(?int $languageUid): self
	{
		$this->languageUid = $languageUid;
		$this->respectSysLanguage = true;
		return $this;
	}

	protected function getLanguageUid(): int
	{
		if ($this->languageUid !== null) {
			return $this->languageUid;
		}

		if ($this->context) {
			try {
				return $this->context->getPropertyFromAspect('language', 'id', 0);
			} catch (\Exception $e) {
				return 0;
			}
		}

		return 0;
	}

	public function setIgnoreEnableFields(bool $ignoreEnableFields): self
	{
		$this->ignoreEnableFields = $ignoreEnableFields;
		return $this;
	}

	public function setIncludeDeleted(bool $includeDeleted): self
	{
		$this->includeDeleted = $includeDeleted;
		return $this;
	}

	public function setReturnRawQueryResult(bool $returnRawQueryResult): self
	{
		$this->returnRawQueryResult = $returnRawQueryResult;
		return $this;
	}

	public function setEnableRuntimeCache(bool $enableRuntimeCache): self
	{
		$this->enableRuntimeCache = $enableRuntimeCache;
		return $this;
	}
	public function reset(): self
	{
		$this->respectStoragePage = false;
		$this->storagePid = 0;
		$this->respectSysLanguage = true;
		$this->ignoreEnableFields = false;
		$this->includeDeleted = false;
		$this->returnRawQueryResult = false;
		$this->enableRuntimeCache = true;
		return $this;
	}
	public function clearRuntimeCache(): self
	{
		$this->recordCache = [];
		$this->allowedUidsCache = [];
		return $this;
	}

	public function clearAllowedUidsCache(): self
	{
		$this->allowedUidsCache = [];
		return $this;
	}

	// ========== CRUD Methods ==========

	public function create(array $data): int
	{
		$builder = $this->getQueryBuilder();
		$builder
			->insert($this->getTableName())
			->values($data)
			->executeStatement();

		return (int)$builder->getConnection()->lastInsertId();
	}

	public function findAll(?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
	{
		$builder = $this->createQuery();

		if ($orderBy) {
			foreach ($orderBy as $field => $direction) {
				$builder->addOrderBy($field, $direction);
			}
		}

		if ($limit !== null) {
			$builder->setMaxResults($limit);
		}

		if ($offset !== null) {
			$builder->setFirstResult($offset);
		}

		return $this->execute($builder);
	}

	public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
	{
		$builder = $this->createQuery();

		foreach ($criteria as $field => $value) {
			$builder->andWhere(
				$builder->expr()->eq($field, $builder->createNamedParameter($value))
			);
		}

		if ($orderBy) {
			foreach ($orderBy as $field => $direction) {
				$builder->addOrderBy($field, $direction);
			}
		}

		if ($limit !== null) {
			$builder->setMaxResults($limit);
		}

		if ($offset !== null) {
			$builder->setFirstResult($offset);
		}

		return $this->execute($builder);
	}

	public function findOneBy(array $criteria): Core\Domain\Record|array|null
	{
		$results = $this->findBy($criteria, limit: 1);
		return $results[0] ?? null;
	}

	public function findByUid(int $uid): Core\Domain\Record|array|null
	{
		if ($this->enableRuntimeCache) {
			$allowedKey = $this->getAllowedUidsCacheKey($uid);
			if (isset($this->allowedUidsCache[$allowedKey])) {
				$actualUid = $this->allowedUidsCache[$allowedKey];

				if ($actualUid === null) {
					return null;
				}

				$recordKey = $this->getRecordCacheKey($actualUid);
				if (isset($this->recordCache[$recordKey])) {
					return $this->recordCache[$recordKey];
				}
			}
		}

		$result = $this->executeFindByUid($uid);
		$this->cacheUidLookupResult($uid, $result);

		return $result;
	}

	protected function executeFindByUid(int $uid): Core\Domain\Record|array|null
	{
		$builder = $this->createQuery();

		if ($this->respectSysLanguage && $this->hasLanguageSupport()) {
			$l10nParentField = $this->getTcaValue('transOrigPointerField');
			if ($l10nParentField) {
				$builder->andWhere(
					$builder->expr()->or(
						$builder->expr()->eq('uid', $builder->createNamedParameter($uid)),
						$builder->expr()->eq($l10nParentField, $builder->createNamedParameter($uid)),
					)
				);
				$results = $this->execute($builder);
				return $results[0] ?? null;
			}
		}

		$builder->andWhere($builder->expr()->eq('uid', $builder->createNamedParameter($uid)));
		$results = $this->execute($builder);
		return $results[0] ?? null;
	}

	protected function cacheUidLookupResult(int $lookupUid, mixed $result): void
	{
		if (!$this->enableRuntimeCache) {
			return;
		}

		$shouldCache = !$this->returnRawQueryResult || $result === null;

		if ($shouldCache) {
			$allowedKey = $this->getAllowedUidsCacheKey($lookupUid);
			$actualUid = $this->getUidFromResult($result);
			$this->allowedUidsCache[$allowedKey] = $actualUid;
		}
	}

	protected function getUidFromResult(mixed $result): ?int
	{
		if ($result === null) {
			return null;
		}

		if (is_array($result)) {
			return (int)$result['uid'];
		}

		return $result->getUid();
	}

	public function countAll(): int
	{
		$builder = $this->createQuery();
		return (int)$builder
			->count('uid')
			->executeQuery()
			->fetchOne();
	}

	public function countBy(array $criteria): int
	{
		$builder = $this->createQuery();

		foreach ($criteria as $field => $value) {
			$builder->andWhere(
				$builder->expr()->eq($field, $builder->createNamedParameter($value))
			);
		}

		return (int)$builder
			->count('uid')
			->executeQuery()
			->fetchOne();
	}

	public function update(int $uid, array $data): void
	{
		$builder = $this->getQueryBuilder();
		$builder
			->update($this->getTableName())
			->where($builder->expr()->eq('uid', $builder->createNamedParameter($uid)));

		foreach ($data as $column => $value) {
			$builder->set($column, $value);
		}

		$builder->executeStatement();
		$this->clearRuntimeCache();
	}

	public function updateBy(array $criteria, array $data): void
	{
		$builder = $this->getQueryBuilder();
		$builder->update($this->getTableName());

		foreach ($criteria as $field => $value) {
			$builder->andWhere(
				$builder->expr()->eq($field, $builder->createNamedParameter($value))
			);
		}

		foreach ($data as $column => $value) {
			$builder->set($column, $value);
		}

		$builder->executeStatement();
		$this->clearRuntimeCache();
	}

	public function remove(int $uid, bool $softDelete = true): void
	{
		if ($softDelete && $this->hasDeletedField()) {
			$deletedField = $this->getTcaValue('delete');
			if ($deletedField) {
				$this->update($uid, [$deletedField => 1]);
				return;
			}
		}

		$this->hardDelete($uid);
	}

	public function removeBy(array $criteria, bool $softDelete = true): void
	{
		if ($softDelete && $this->hasDeletedField()) {
			$deletedField = $this->getTcaValue('delete');
			if ($deletedField) {
				$this->updateBy($criteria, [$deletedField => 1]);
				return;
			}
		}

		$builder = $this->getQueryBuilder();
		$builder->delete($this->getTableName());

		foreach ($criteria as $field => $value) {
			$builder->andWhere(
				$builder->expr()->eq($field, $builder->createNamedParameter($value))
			);
		}

		$builder->executeStatement();
		$this->clearRuntimeCache();
	}

	protected function hardDelete(int $uid): void
	{
		$builder = $this->getQueryBuilder();
		$builder
			->delete($this->getTableName())
			->where($builder->expr()->eq('uid', $builder->createNamedParameter($uid)))
			->executeStatement();

		$this->clearRuntimeCache();
	}

	// ========== Query Building ==========

	protected function createQuery(): QueryBuilder
	{
		$builder = $this->getQueryBuilder();
		$builder->select('*')->from($this->getTableName());

		$this->applyRestrictions($builder);

		if ($this->respectStoragePage) {
			$builder->andWhere(
				$builder->expr()->eq('pid', $builder->createNamedParameter($this->storagePid))
			);
		}

		if ($this->respectSysLanguage && $this->hasLanguageSupport()) {
			$languageField = $this->getTcaValue('languageField');
			$builder->andWhere(
				$builder->expr()->eq($languageField, $builder->createNamedParameter($this->getLanguageUid()))
			);
		}

		return $builder;
	}

	protected function applyRestrictions(QueryBuilder $builder): void
	{
		$restrictions = $builder->getRestrictions()->removeAll();

		if ($this->context) {
			$workspaceId = $this->context->getPropertyFromAspect('workspace', 'id', 0);
			if ($workspaceId > 0) {
				$restrictions->add(new WorkspaceRestriction($workspaceId));
			}
		}

		if (!$this->includeDeleted && $this->hasDeletedField()) {
			$restrictions->add(new DeletedRestriction());
		}

		if (!$this->ignoreEnableFields) {
			if ($this->hasHiddenField()) {
				$includeHidden = $this->context
					? $this->context->getPropertyFromAspect('visibility', 'includeHiddenContent', false)
					: false;

				if (!$includeHidden) {
					$restrictions->add(new HiddenRestriction());
				}
			}

			if ($this->hasStarttimeField()) {
				$restrictions->add(new StartTimeRestriction());
			}

			if ($this->hasEndtimeField()) {
				$restrictions->add(new EndTimeRestriction());
			}
		}
	}

	protected function execute(QueryBuilder $builder): array
	{
		$rows = $builder->executeQuery()->fetchAllAssociative();

		if ($this->returnRawQueryResult) {
			return $rows;
		}

		return $this->toRecords($rows);
	}

	protected function toRecords(array $rows): array
	{
		if (empty($rows)) {
			return [];
		}

		$records = [];
		foreach ($rows as $row) {
			if (!$row) {
				continue;
			}

			$uid = $row['uid'];

			if ($this->enableRuntimeCache) {
				$recordKey = $this->getRecordCacheKey($uid);
				if (isset($this->recordCache[$recordKey])) {
					$records[] = $this->recordCache[$recordKey];
					continue;
				}
			}

			$record = $this->recordFactory->createResolvedRecordFromDatabaseRow(
				$this->getTableName(),
				$row
			);

			if ($this->enableRuntimeCache) {
				$recordKey = $this->getRecordCacheKey($uid);
				$this->recordCache[$recordKey] = $record;
			}

			$records[] = $record;
		}

		return $records;
	}

	protected function getQueryBuilder(): QueryBuilder
	{
		return $this->connectionPool->getQueryBuilderForTable($this->getTableName());
	}

	// ========== Runtime Cache Helpers ==========

	protected function getRecordCacheKey(int $uid): string
	{
		return (string)$uid;
	}

	protected function getAllowedUidsCacheKey(int $uid): string
	{
		return sprintf(
			'%d|%d|%d|%d|%d',
			$uid,
			$this->storagePid,
			(int)$this->respectStoragePage,
			(int)$this->ignoreEnableFields,
			(int)$this->includeDeleted
		);
	}

	// ========== TCA Helpers ==========

	protected function getTca(): ?array
	{
		return $GLOBALS['TCA'][$this->getTableName()] ?? null;
	}

	protected function getTcaValue(string $path): mixed
	{
		$tca = $this->getTca();
		if (!$tca) {
			return null;
		}

		if (str_contains($path, '.')) {
			$parts = explode('.', $path);
			$value = $tca;
			foreach ($parts as $part) {
				if (!isset($value[$part])) {
					return null;
				}
				$value = $value[$part];
			}
			return $value;
		}

		return $tca['ctrl'][$path] ?? null;
	}

	protected function hasLanguageSupport(): bool
	{
		return !empty($this->getTcaValue('languageField'));
	}

	protected function hasDeletedField(): bool
	{
		return !empty($this->getTcaValue('delete'));
	}

	protected function hasHiddenField(): bool
	{
		return !empty($this->getTcaValue('enablecolumns')['disabled'] ?? null);
	}

	protected function hasStarttimeField(): bool
	{
		return !empty($this->getTcaValue('enablecolumns')['starttime'] ?? null);
	}

	protected function hasEndtimeField(): bool
	{
		return !empty($this->getTcaValue('enablecolumns')['endtime'] ?? null);
	}
}