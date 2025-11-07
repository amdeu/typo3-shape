<?php

namespace Amdeu\Shape\Form\Finisher;

use Amdeu\Shape\Repository;

class SaveToDatabaseFinisher extends AbstractFinisher
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

	public function executeInternal(): void
	{
		if (!$this->settings['table']) {
			$this->logger->warning('Table name is empty', $this->getLogContext());
			return;
		}

		$repository = $this->genericRepositoryFactory
			->forTable($this->settings['table'])
			->reset();

		$values = [
			'pid' => (int)($this->settings['storagePage'] ?: $this->getPlugin()->getPid() ?? $this->getForm()->getPid()),
			'tstamp' => time(),
		];

		foreach ($this->settings['columns'] as $item) {
			$column = $item['column'] ?? null;
			if (!$column) {
				continue;
			}
			$name = $column['name'];
			$value = $this->parseWithValues($column['value']);
			$values[$name] = $value;
		}

		if ($this->settings['whereColumn'] && $this->settings['whereValue']) {
			$whereColumn = $this->settings['whereColumn'];
			$whereValue = $this->parseWithValues($this->settings['whereValue']);

			if (empty($whereValue)) {
				$this->logger->error('WHERE value is empty - preventing mass update', $this->getLogContext([
					'table' => $this->settings['table'],
				]));
				return;
			}

			try {
				$repository->updateBy([$whereColumn => $whereValue], $values);
				$this->logger->info('Record updated', $this->getLogContext([
					'table' => $this->settings['table'],
				]));
			} catch (\Exception $e) {
				$this->logger->error('Failed to update record', $this->getLogContext([
					'table' => $this->settings['table'],
					'error' => $e->getMessage(),
				]));
			}
		} else {
			$values['crdate'] = time();
			try {
				$newUid = $repository->create($values);
				$this->logger->info('Record created', $this->getLogContext([
					'table' => $this->settings['table'],
					'uid' => $newUid,
				]));
			} catch (\Exception $e) {
				$this->logger->error('Failed to create record', $this->getLogContext([
					'table' => $this->settings['table'],
					'error' => $e->getMessage(),
				]));
			}
		}
	}
}