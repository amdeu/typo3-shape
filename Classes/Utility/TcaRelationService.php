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
		$originQuery = $this->connectionPool->getQueryBuilderForTable($originTable);
		$targetQuery = $this->connectionPool->getQueryBuilderForTable($targetTable);

		// empty target column
		if ($emptyTargetColumn) {
			$targetQuery
				->update($targetTable)
				->set($targetColumn, '')
				->executeStatement();
		}
		// find all target records
		$targetRows = $targetQuery
			->select('uid', $targetColumn)
			->from($targetTable)
			->executeQuery()
			->fetchAllAssociative();
		// for each record in target table, find all records in origin table that have target uid in origin column
		// and update target column with comma-separated list of origin uids
		foreach ($targetRows as $row) {
			$targetUid = $row['uid'];
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
			$oppositeUids = array_map(function ($oRow) {
				return $oRow['uid'];
			}, $oppositeRows);
			$oppositeUids = implode(',', $oppositeUids);
			$targetQuery
				->update($targetTable)
				->set($targetColumn, $oppositeUids)
				->where(
					$targetQuery->expr()->eq('uid', $targetUid)
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

	/**
	 * Mirrors relations between forms and finishers based on TCA tx_shape_form.finishers type
	 */
	public function mirrorCurrentFormFinisherRelations(): void
	{
		$finishersType = $GLOBALS['TCA']['tx_shape_form']['columns']['finishers']['config']['type'];
		if (in_array($finishersType, ['select', 'group'])) {
			$this->mirrorCSVRelation(
				'tx_shape_form',
				'finishers',
				'tx_shape_finisher',
				'form_parents'
			);
		} else if ($finishersType === 'inline') {
			$this->mirrorCSVRelation(
				'tx_shape_finisher',
				'form_parents',
				'tx_shape_form',
				'finishers'
			);
		}
	}
}