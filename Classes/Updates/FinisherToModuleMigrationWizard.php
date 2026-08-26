<?php

declare(strict_types=1);

namespace Amdeu\Shape\Updates;

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\Confirmation;
use TYPO3\CMS\Install\Updates\ConfirmableInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use Amdeu\Shape\Form\Module\ModuleRegistry;

/**
 * Migrates tx_shape_finisher rows to tx_shape_module_configuration rows, and separately renames the
 * EmailConsent type's `splitFinisherExecution` FlexForm field to `splitModuleExecution` wherever it's
 * still stored under the old name.
 *
 * Finisher -> Module row migration:
 * The Finisher system is being replaced by the Module system; both share the same FlexForm field
 * layout per type (only the sheet/DS registration differs), so a Finisher row's `settings` value can
 * be copied verbatim into the new Module row, with two known exceptions applied while copying:
 * - SendEmail's `template` field stores a value like "Finisher/SendEmail/Default", remapped to
 *   "Module/SendEmail/Default" (see remapSendEmailTemplateValue()).
 * - EmailConsent's `splitFinisherExecution` field is renamed to `splitModuleExecution` (see
 *   renameFlexFormField()), same as below.
 *
 * A Finisher type is matched to a Module type by naming convention: `Ns\Finisher\XyzFinisher` is
 * expected to have a counterpart `Ns\Module\XyzModule` registered in ModuleRegistry. This covers all
 * built-in types and any third-party type following the same convention. Types that can't be resolved
 * this way (e.g. a custom Finisher with no Module equivalent) are left untouched and reported.
 *
 * Workspace-versioned records (t3ver_wsid != 0) are intentionally excluded; only live records are
 * migrated.
 *
 * Migrated Finisher rows are soft-deleted (not hard-deleted), so this wizard is safe to run again -
 * already-migrated rows are simply skipped, and the originals remain recoverable via the recycler
 * until a later cleanup.
 *
 * The tx_shape_finisher table itself no longer ships with this extension version (TCA and
 * ext_tables.sql removed) - getMigratableFinisherRows() checks for its existence first, so this
 * wizard degrades to "nothing to migrate" rather than erroring on a fresh install or a site that has
 * already dropped the table.
 *
 * splitFinisherExecution -> splitModuleExecution field rename:
 * This field was renamed on the Module side's FlexForm regardless of a row's origin, so any existing
 * tx_shape_module_configuration row of type `emailConsent` created directly (never having a Finisher
 * counterpart) also still carries the old field name and needs the same rename. This wizard sweeps
 * those independently of the Finisher migration above (see getEmailConsentModuleRowsNeedingFieldRename()).
 */
#[UpgradeWizard('shape_finisherToModuleMigration')]
class FinisherToModuleMigrationWizard implements UpgradeWizardInterface, ConfirmableInterface, ChattyInterface
{
	protected const SOURCE_TABLE = 'tx_shape_finisher';
	protected const TARGET_TABLE = 'tx_shape_module_configuration';

	protected ?OutputInterface $output = null;

	public function __construct(
		protected readonly ConnectionPool $connectionPool,
	) {}

	public function getTitle(): string
	{
		return 'EXT:shape: Migrate Finisher configuration rows to Module configuration rows';
	}

	public function getDescription(): string
	{
		return 'The Finisher system (tx_shape_finisher) is being replaced by the Module system '
			. '(tx_shape_module_configuration). This wizard creates an equivalent Module row for every '
			. 'still-existing, non-workspace Finisher row it can confidently map (built-in types, plus any '
			. 'third-party type following the "XyzFinisher" -> "XyzModule" naming convention with a '
			. 'registered Module identifier), copies its settings and form relation across, and '
			. 'soft-deletes the migrated Finisher row. Rows it cannot map are left untouched and listed '
			. 'in the wizard output instead. Safe to run again: already-migrated (deleted) Finisher rows '
			. 'are simply skipped. '
			. 'It also renames the EmailConsent module type\'s "splitFinisherExecution" FlexForm field to '
			. '"splitModuleExecution" in any tx_shape_module_configuration row that still has it under the '
			. 'old name, regardless of whether that row came from a migrated Finisher or was always a Module.';
	}

	public function getPrerequisites(): array
	{
		return [];
	}

	public function getConfirmation(): Confirmation
	{
		return new Confirmation(
			'Migrate Finisher rows to Module rows?',
			'This creates new tx_shape_module_configuration rows and soft-deletes the tx_shape_finisher '
			. 'rows once migrated (recoverable via the backend recycler). Take a database backup before '
			. 'running this.',
			false,
			'Yes, migrate now',
			'No, not now',
			false
		);
	}

	public function setOutput(OutputInterface $output): void
	{
		$this->output = $output;
	}

	public function updateNecessary(): bool
	{
		return $this->getMigratableFinisherRows() !== []
			|| $this->getEmailConsentModuleRowsNeedingFieldRename() !== [];
	}

	public function executeUpdate(): bool
	{
		$rows = $this->getMigratableFinisherRows();
		// Default-language rows first, so translations can remap l10n_parent/l10n_source to the new uid.
		usort($rows, static fn(array $a, array $b) => $a['sys_language_uid'] <=> $b['sys_language_uid']);

		$uidMap = [];
		$skipped = [];

		foreach ($rows as $row) {
			$moduleIdentifier = $this->resolveModuleIdentifier((string)$row['type']);
			if ($moduleIdentifier === null) {
				$skipped[] = sprintf(
					'finisher uid %d: no matching Module type registered for "%s"',
					$row['uid'],
					$row['type']
				);
				continue;
			}

			$l10nParentOld = (int)$row['l10n_parent'];
			if ($l10nParentOld > 0 && !isset($uidMap[$l10nParentOld])) {
				$skipped[] = sprintf(
					'finisher uid %d: its language parent (uid %d) was not migrated, skipping to avoid an orphaned translation',
					$row['uid'],
					$l10nParentOld
				);
				continue;
			}

			$settings = match ($moduleIdentifier) {
				'sendEmail' => $this->remapSendEmailTemplateValue((string)$row['settings']),
				'emailConsent' => $this->renameFlexFormField((string)$row['settings'], 'splitFinisherExecution', 'splitModuleExecution'),
				default => (string)$row['settings'],
			};

			$newUid = $this->createModuleRow(
				$row,
				$moduleIdentifier,
				$settings,
				$this->resolveMappedUid($uidMap, $l10nParentOld),
				$this->resolveMappedUid($uidMap, (int)($row['l10n_source'] ?? 0))
			);
			$uidMap[(int)$row['uid']] = $newUid;

			$this->markFinisherAsMigrated((int)$row['uid']);
			$this->log(sprintf('Migrated finisher uid %d to module uid %d (%s).', $row['uid'], $newUid, $moduleIdentifier));
		}

		$this->log(sprintf('Done: migrated %d finisher row(s).', count($uidMap)));
		if ($skipped) {
			$this->log(sprintf('Skipped %d finisher row(s):', count($skipped)));
			foreach ($skipped as $message) {
				$this->log(' - ' . $message);
			}
		}

		$renamed = 0;
		foreach ($this->getEmailConsentModuleRowsNeedingFieldRename() as $row) {
			$newSettings = $this->renameFlexFormField((string)$row['settings'], 'splitFinisherExecution', 'splitModuleExecution');
			if ($newSettings === $row['settings']) {
				continue;
			}
			$updateBuilder = $this->connectionPool->getQueryBuilderForTable(self::TARGET_TABLE);
			$updateBuilder
				->update(self::TARGET_TABLE)
				->where($updateBuilder->expr()->eq('uid', $updateBuilder->createNamedParameter($row['uid'])))
				->set('settings', $newSettings)
				->executeStatement();
			$renamed++;
		}
		$this->log(sprintf('Renamed splitFinisherExecution -> splitModuleExecution in %d existing emailConsent module row(s).', $renamed));

		return true;
	}

	/**
	 * Derives the expected Module class name from a Finisher class name by naming convention
	 * (`Ns\Finisher\XyzFinisher` -> `Ns\Module\XyzModule`) and resolves it to a registered Module
	 * identifier. Returns null if the class doesn't follow the convention or isn't registered.
	 */
	protected function resolveModuleIdentifier(string $finisherClassName): ?string
	{
		if (!str_contains($finisherClassName, '\\Finisher\\') || !str_ends_with($finisherClassName, 'Finisher')) {
			return null;
		}
		$moduleClassName = str_replace('\\Finisher\\', '\\Module\\', $finisherClassName);
		$moduleClassName = substr($moduleClassName, 0, -strlen('Finisher')) . 'Module';

		foreach (ModuleRegistry::getAll() as $identifier => $registeredClassName) {
			if ($registeredClassName === $moduleClassName) {
				return $identifier;
			}
		}
		return null;
	}

	/**
	 * The SendEmail Finisher/Module types share an identical FlexForm layout, but the `template`
	 * field's stored value is a registry key that differs by prefix ("Finisher/..." vs "Module/...").
	 * Rewrites that one field's value in the raw FlexForm XML, leaving everything else untouched.
	 */
	protected function remapSendEmailTemplateValue(string $settingsXml): string
	{
		if (!str_contains($settingsXml, '<template>') || $settingsXml === '') {
			return $settingsXml;
		}

		$previousUseErrors = libxml_use_internal_errors(true);
		$xml = simplexml_load_string($settingsXml);
		libxml_use_internal_errors($previousUseErrors);

		if ($xml === false) {
			return $settingsXml;
		}

		$changed = false;
		foreach ($xml->xpath('//field[@index="template"]/value') ?: [] as $value) {
			$current = (string)$value;
			if (str_starts_with($current, 'Finisher/')) {
				$value[0] = 'Module/' . substr($current, strlen('Finisher/'));
				$changed = true;
			}
		}

		if (!$changed) {
			return $settingsXml;
		}

		$rewritten = $xml->asXML();
		return $rewritten !== false ? $rewritten : $settingsXml;
	}

	/**
	 * Renames a FlexForm field's `index` attribute in raw stored FlexForm XML, leaving its value and
	 * every other field untouched. No-op (returns the input unchanged) if the field isn't present.
	 */
	protected function renameFlexFormField(string $settingsXml, string $oldFieldName, string $newFieldName): string
	{
		if ($settingsXml === '' || !str_contains($settingsXml, $oldFieldName)) {
			return $settingsXml;
		}

		$previousUseErrors = libxml_use_internal_errors(true);
		$xml = simplexml_load_string($settingsXml);
		libxml_use_internal_errors($previousUseErrors);

		if ($xml === false) {
			return $settingsXml;
		}

		$changed = false;
		foreach ($xml->xpath(sprintf('//field[@index="%s"]', $oldFieldName)) ?: [] as $field) {
			$field['index'] = $newFieldName;
			$changed = true;
		}

		if (!$changed) {
			return $settingsXml;
		}

		$rewritten = $xml->asXML();
		return $rewritten !== false ? $rewritten : $settingsXml;
	}

	/**
	 * Finds tx_shape_module_configuration rows of type `emailConsent` whose stored FlexForm settings
	 * still use the old `splitFinisherExecution` field name, regardless of whether the row came from a
	 * migrated Finisher or was always a Module. The LIKE pre-filter avoids parsing XML for rows that
	 * plainly don't need it.
	 */
	protected function getEmailConsentModuleRowsNeedingFieldRename(): array
	{
		$queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TARGET_TABLE);
		$queryBuilder->getRestrictions()->removeAll();
		return $queryBuilder
			->select('uid', 'settings')
			->from(self::TARGET_TABLE)
			->where(
				$queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter('emailConsent')),
				$queryBuilder->expr()->eq('t3ver_wsid', 0),
				$queryBuilder->expr()->like('settings', $queryBuilder->createNamedParameter('%splitFinisherExecution%')),
			)
			->executeQuery()
			->fetchAllAssociative();
	}

	protected function resolveMappedUid(array $uidMap, int $oldUid): int
	{
		return $oldUid > 0 ? ($uidMap[$oldUid] ?? 0) : 0;
	}

	protected function createModuleRow(
		array $sourceRow,
		string $moduleIdentifier,
		string $settings,
		int $l10nParent,
		int $l10nSource
	): int
	{
		$queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TARGET_TABLE);
		$queryBuilder
			->insert(self::TARGET_TABLE)
			->values([
				'pid' => (int)$sourceRow['pid'],
				'tstamp' => time(),
				'crdate' => time(),
				'sorting' => (int)$sourceRow['sorting'],
				'sys_language_uid' => (int)$sourceRow['sys_language_uid'],
				'l10n_parent' => $l10nParent,
				'l10n_source' => $l10nSource,
				'l10n_diffsource' => (string)$sourceRow['l10n_diffsource'],
				'hidden' => (int)$sourceRow['hidden'],
				'title' => (string)$sourceRow['title'],
				'type' => $moduleIdentifier,
				'condition' => (string)$sourceRow['condition'],
				'settings' => $settings,
				'form_parents' => (string)$sourceRow['form_parents'],
			])
			->executeStatement();

		return (int)$queryBuilder->getConnection()->lastInsertId();
	}

	protected function markFinisherAsMigrated(int $uid): void
	{
		$queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::SOURCE_TABLE);
		$queryBuilder
			->update(self::SOURCE_TABLE)
			->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid)))
			->set('deleted', 1)
			->executeStatement();
	}

	protected function getMigratableFinisherRows(): array
	{
		$connection = $this->connectionPool->getConnectionForTable(self::SOURCE_TABLE);
		// The source table itself is gone (never existed on a fresh install of this version, or a
		// site already dropped it after a previous run) - nothing to migrate, not an error.
		if (!$connection->createSchemaManager()->tablesExist([self::SOURCE_TABLE])) {
			return [];
		}

		$queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::SOURCE_TABLE);
		$queryBuilder->getRestrictions()->removeAll();
		return $queryBuilder
			->select('*')
			->from(self::SOURCE_TABLE)
			->where(
				$queryBuilder->expr()->eq('deleted', 0),
				$queryBuilder->expr()->eq('t3ver_wsid', 0),
			)
			->executeQuery()
			->fetchAllAssociative();
	}

	protected function log(string $message): void
	{
		$this->output?->writeln($message);
	}
}
