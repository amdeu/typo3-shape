<?php

namespace Amdeu\Shape\Utility;

use TYPO3\CMS\Core;

/**
 * Handles mirroring of comma-separated relation fields between tables
 *
 * When switching between field types (group/select <-> inline), the relation data
 * needs to be mirrored between tables since these field types expect relations
 * to be stored in opposite tables (Inline fields store parent uid in child records, while group/select fields store child uids in parent records).
 *
 */
class TcaRelationService
{
	public function __construct(
		protected Core\Database\ConnectionPool $connectionPool
	)
	{
	}

	/**
	 * Mirrors comma-separated relations between two tables
	 */
	public function mirrorCSVRelation(
		string $originTable,
		string $originColumn,
		string $targetTable,
		string $targetColumn,
		bool $emptyTargetColumn = true): void
	{

		// empty target column
		if ($emptyTargetColumn) {
			$this->connectionPool->getQueryBuilderForTable($targetTable)
				->update($targetTable)
				->set($targetColumn, '')
				->executeStatement();
		}
		// find all target records
		$targetRows = $this->connectionPool->getQueryBuilderForTable($targetTable)
			->select('uid', $targetColumn)
			->from($targetTable)
			->executeQuery()
			->fetchAllAssociative();
		// for each record in target table, find all records in origin table that have target uid in origin column
		// and update target column with comma-separated list of origin uids
		foreach ($targetRows as $row) {
			$targetUid = (int)$row['uid'];

			$originQuery = $this->connectionPool->getQueryBuilderForTable($originTable);
			$oppositeRows = $originQuery
				->select('uid')
				->from($originTable)
				->where(
					$originQuery->expr()->inSet($originColumn, (string)$targetUid)
				)
				->executeQuery()
				->fetchAllAssociative();
			if (empty($oppositeRows)) {
				continue;
			}
			$oppositeUids = implode(',', array_map(
				static fn ($oRow) => (int)$oRow['uid'],
				$oppositeRows
			));

			$updateQuery = $this->connectionPool->getQueryBuilderForTable($targetTable);
			$updateQuery
				->update($targetTable)
				->set($targetColumn, $oppositeUids)
				->where(
					$updateQuery->expr()->eq(
						'uid',
						$updateQuery->createNamedParameter($targetUid, Core\Database\Connection::PARAM_INT)
					)
				)
				->executeStatement();
		}
	}

	/**
	 * Mirrors relations between form pages and fields based on TCA tx_shape_form_page.fields type
	 */
	public function mirrorCurrentPageFieldRelations(): void
	{
		$fieldsType = $GLOBALS['TCA']['tx_shape_form_page']['columns']['fields']['config']['type'];
		if (in_array($fieldsType, ['select', 'group'])) {
			$this->mirrorCSVRelation(
				'tx_shape_form_page',
				'fields',
				'tx_shape_field',
				'page_parents'
			);
		} else if ($fieldsType === 'inline') {
			$this->mirrorCSVRelation(
				'tx_shape_field',
				'page_parents',
				'tx_shape_form_page',
				'fields'
			);
		}
	}
}