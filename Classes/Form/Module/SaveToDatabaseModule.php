<?php

namespace Amdeu\Shape\Form\Module;

use Amdeu\Shape\Form;
use Amdeu\Shape\Repository;

class SaveToDatabaseModule extends AbstractModule
{
	protected array $settings = [
		'table' => '',
		'storagePage' => '',
		'whereColumn' => '',
		'whereValue' => '',
		'columns' => [],
	];

	public function __construct(
		protected Repository\GenericRepositoryFactory $genericRepositoryFactory
	) {
	}

	#[AsModuleEventListener]
	public function onFormFinish(Form\FormFinishEvent $event): void
	{
		$table = (string)$this->settings['table'];
		if (!$table) {
			$this->logger->warning('Table name is empty', $this->getLogContext());
			return;
		}
		if (!isset($GLOBALS['TCA'][$table])) {
			$this->logger->error('Unknown table', $this->getLogContext(['table' => $table]));
			return;
		}

		$repository = $this->genericRepositoryFactory
			->forTable($table)
			->reset();

		$values = [
			'pid' => (int)($this->settings['storagePage'] ?: $this->getPlugin()->getPid() ?? $this->getForm()->getPid()),
			'tstamp' => time(),
		];

		foreach ($this->settings['columns'] as $item) {
			$column = $item['column'] ?? null;
			if (!$column || !isset($column['name'])) {
				continue;
			}
			$name = (string)$column['name'];
			if (!$this->isValidColumnName($name)) {
				$this->logger->warning('Skipped column with invalid name', $this->getLogContext(['table' => $table, 'column' => $name]));
				continue;
			}
			$values[$name] = $this->parseWithValues($column['value'] ?? '');
		}

		if ($this->settings['whereColumn'] && $this->settings['whereValue']) {
			$whereColumn = (string)$this->settings['whereColumn'];
			if (!$this->isValidColumnName($whereColumn)) {
				$this->logger->error('Invalid WHERE column name', $this->getLogContext(['table' => $table, 'column' => $whereColumn]));
				return;
			}
			$whereValue = $this->parseWithValues($this->settings['whereValue']);

			if (empty($whereValue)) {
				$this->logger->error('WHERE value is empty - preventing mass update', $this->getLogContext([
					'table' => $table,
				]));
				return;
			}

			try {
				$repository->updateBy([$whereColumn => $whereValue], $values);
				$this->logger->info('Record updated', $this->getLogContext([
					'table' => $table,
				]));
			} catch (\Exception $e) {
				$this->logger->error('Failed to update record', $this->getLogContext([
					'table' => $table,
					'error' => $e->getMessage(),
				]));
			}
		} else {
			$values['crdate'] = time();
			try {
				$newUid = $repository->create($values);
				$this->logger->info('Record created', $this->getLogContext([
					'table' => $table,
					'uid' => $newUid,
				]));
			} catch (\Exception $e) {
				$this->logger->error('Failed to create record', $this->getLogContext([
					'table' => $table,
					'error' => $e->getMessage(),
				]));
			}
		}
	}

	/**
	 * Column names reach the query builder as identifiers; keep them to a safe character set
	 * (the builder quotes them too, this just fails a misconfiguration early and clearly).
	 */
	protected function isValidColumnName(string $name): bool
	{
		return (bool)preg_match('/^[a-zA-Z0-9_]+$/', $name);
	}
}