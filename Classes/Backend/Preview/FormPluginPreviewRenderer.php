<?php

declare(strict_types=1);

namespace Amdeu\Shape\Backend\Preview;

use Amdeu\Shape\Utility\TcaUtility;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Preview renderer for the "shape_form" plugin.
 *
 * Renders the regular standard preview and appends a compact overview table for
 * the attached tx_shape_form record: form actions (edit / info / history / list
 * module) plus a row of edit / delete / sort controls for every form page and
 * module. Edit links open the contextual slide-in on TYPO3 v14 and fall back to
 * the regular edit form on v13.
 */
#[Autoconfigure(public: true)]
class FormPluginPreviewRenderer extends StandardContentPreviewRenderer
{
	private const WEB_LIST_LABELS = 'LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:';

	private bool $sortableModuleLoaded = false;
	private bool $contextualEditModuleLoaded = false;
	private ?bool $isV14 = null;
	private ?array $userTsOptions = null;

	public function renderPageModulePreviewContent(GridColumnItem $item): string
	{
		return parent::renderPageModulePreviewContent($item) . $this->renderFormPreview($item);
	}

	protected function renderFormPreview(GridColumnItem $item): string
	{
		$row = $this->getContentRow($item);
		$languageService = $this->getLanguageService();

		$formUid = $this->resolveFormUid((string)($row['pi_flexform'] ?? ''));
		if ($formUid <= 0) {
			return $this->warning($languageService->sL(TcaUtility::t('preview.form.missing')));
		}

		$formRecord = BackendUtility::getRecord('tx_shape_form', $formUid);
		if ($formRecord === null) {
			return $this->warning(sprintf($languageService->sL(TcaUtility::t('preview.form.notFound')), $formUid));
		}

		// Mirror the list module: without any access to the form table there is nothing to show.
		$backendUser = $this->getBackendUser();
		if (!$backendUser->check('tables_select', 'tx_shape_form') && !$backendUser->check('tables_modify', 'tx_shape_form')) {
			return '';
		}

		$returnUrl = GeneralUtility::getIndpEnv('REQUEST_URI');

		$rows = [
			$this->tableRow(
				$languageService->sL(TcaUtility::t('form.ctrl.title')),
				$this->renderFormActions($formRecord, $returnUrl)
			),
		];
		foreach (
			[
				['tx_shape_form_page', 'form_parent', 'form.pages', 'preview.formPage.edit', 'preview.formPage.new'],
				['tx_shape_module_configuration', 'form_parents', 'form.modules', 'preview.module.edit', 'preview.module.new'],
			] as [$childTable, $parentField, $labelKey, $editKey, $newKey]
		) {
			$content = $this->renderChildButtons($childTable, $parentField, $formRecord, $returnUrl, $editKey, $newKey);
			if ($content !== '') {
				$rows[] = $this->tableRow($languageService->sL(TcaUtility::t($labelKey)), $content);
			}
		}

		return '<div class="shape-form-preview table-fit mt-2">'
			. '<table class="table table-sm mb-0"><tbody>' . implode('', $rows) . '</tbody></table>'
			. '</div>';
	}

	protected function tableRow(string $label, string $content): string
	{
		return '<tr>'
			. '<th scope="row" class="text-nowrap fw-bold align-middle pe-4" style="width:1%">' . htmlspecialchars($label) . '</th>'
			. '<td class="align-middle">' . $content . '</td>'
			. '</tr>';
	}

	/**
	 * Action button group for the form record itself: edit, record info, history, list module.
	 */
	protected function renderFormActions(array $formRecord, string $returnUrl): string
	{
		$languageService = $this->getLanguageService();
		$iconFactory = GeneralUtility::makeInstance(IconFactory::class);

		$formUid = (int)$formRecord['uid'];
		$title = BackendUtility::getRecordTitle('tx_shape_form', $formRecord) ?: (string)$formUid;
		$canEditForm = $this->canEditRecord('tx_shape_form', $formRecord);

		$editTitle = sprintf($languageService->sL(TcaUtility::t('preview.form.edit')), $title);

		$buttons = [
			// Form label + edit
			$this->editTrigger(
				'tx_shape_form',
				$formUid,
				$returnUrl,
				$iconFactory->getIconForRecord('tx_shape_form', $formRecord, IconSize::SMALL)->render()
					. '<span class="ms-1">' . htmlspecialchars($title) . '</span>',
				$editTitle,
				'btn btn-default btn-borderless',
				$canEditForm
			),
		];

		// Record info window (references etc.) - read action, needs list access to the table
		if ($this->getBackendUser()->check('tables_select', 'tx_shape_form')) {
			$infoTitle = sprintf($languageService->sL(TcaUtility::t('preview.form.info')), $title);
			$buttons[] = sprintf(
				'<button type="button" class="btn btn-default btn-borderless" title="%s" aria-label="%s"'
					. ' data-dispatch-action="TYPO3.InfoWindow.showItem" data-dispatch-args-list="%s">%s</button>',
				htmlspecialchars($infoTitle),
				htmlspecialchars($infoTitle),
				htmlspecialchars('tx_shape_form,' . $formUid),
				$iconFactory->getIcon('actions-document-info', IconSize::SMALL)->render()
			);
		}

		// Change history / undo - write action, plus the options.showHistory userTSconfig
		if ($canEditForm && $this->historyEnabled('tx_shape_form')) {
			$historyTitle = sprintf($languageService->sL(TcaUtility::t('preview.form.history')), $title);
			$historyUrl = (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('record_history', [
				'element' => 'tx_shape_form:' . $formUid,
				'returnUrl' => $returnUrl,
			]);
			$buttons[] = sprintf(
				'<a class="btn btn-default btn-borderless" href="%s#latest" title="%s" aria-label="%s">%s</a>',
				htmlspecialchars($historyUrl),
				htmlspecialchars($historyTitle),
				htmlspecialchars($historyTitle),
				$iconFactory->getIcon('actions-document-history-open', IconSize::SMALL)->render()
			);
		}

		// List module overview of the form's storage page - only if that module is accessible
		if ($this->listModuleAccessible()) {
			$listModule = $this->isV14() ? 'records' : 'web_list';
			$listTitle = $languageService->sL(TcaUtility::t('preview.form.list'));
			$buttons[] = sprintf(
				'<button type="button" class="btn btn-default btn-borderless" title="%s" aria-label="%s"'
					. ' data-dispatch-action="TYPO3.ModuleMenu.showModule" data-dispatch-args-list="%s">%s</button>',
				htmlspecialchars($listTitle),
				htmlspecialchars($listTitle),
				htmlspecialchars($listModule . ',id=' . (int)$formRecord['pid']),
				$iconFactory->getIcon('actions-list', IconSize::SMALL)->render()
			);
		}

		return '<div class="btn-group btn-group-sm" role="group">' . implode('', $buttons) . '</div>';
	}

	/**
	 * One button per child record (form page / module) linking to its edit form,
	 * with delete and drag & drop sorting, plus a trailing button to create a new one.
	 */
	protected function renderChildButtons(
		string $table,
		string $parentField,
		array $formRecord,
		string $returnUrl,
		string $editTitleKey,
		string $newTitleKey
	): string {
		$formUid = (int)$formRecord['uid'];

		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
		$queryBuilder->getRestrictions()
			->removeAll()
			->add(GeneralUtility::makeInstance(DeletedRestriction::class));
		$records = $queryBuilder
			->select('*')
			->from($table)
			->where(
				$queryBuilder->expr()->eq(
					$parentField,
					$queryBuilder->createNamedParameter($formUid, Connection::PARAM_INT)
				)
			)
			->orderBy('sorting')
			->executeQuery()
			->fetchAllAssociative();
		$records = array_values($records);

		$languageService = $this->getLanguageService();
		$iconFactory = GeneralUtility::makeInstance(IconFactory::class);
		$fallbackLabel = $languageService->sL((string)($GLOBALS['TCA'][$table]['ctrl']['title'] ?? ''));
		$editTitlePattern = $languageService->sL(TcaUtility::t($editTitleKey));

		$storagePid = (int)$formRecord['pid'];
		$canCreate = $this->canModifyTable($table, $storagePid);
		$deleteDisabled = (bool)trim($this->userTsOption('disableDelete', $table));
		$count = count($records);

		// Per-record edit access (checks the record's own page, editlock, language & type).
		$recordEditable = [];
		foreach ($records as $record) {
			$recordEditable[(int)$record['uid']] = $this->canEditRecord($table, $record);
		}

		// Sorting is a "move" (an edit), so every listed record must be editable
		$sortable = $count >= 2 && !in_array(false, $recordEditable, true);
		if ($sortable) {
			$this->loadSortableModule();
		}

		$deleteLabel = $languageService->sL(self::WEB_LIST_LABELS . 'delete');
		$dragTitle = $languageService->sL(TcaUtility::t('preview.dragToReorder'));

		$groups = [];
		foreach ($records as $index => $record) {
			$uid = (int)$record['uid'];
			$editable = $recordEditable[$uid];
			$label = BackendUtility::getRecordTitle($table, $record)
				?: trim(sprintf('%s %d', $fallbackLabel, $index + 1));

			$actions = [
				$this->editTrigger(
					$table,
					$uid,
					$returnUrl,
					$iconFactory->getIconForRecord($table, $record, IconSize::SMALL)->render()
						. '<span class="ms-1">' . htmlspecialchars($label) . '</span>',
					sprintf($editTitlePattern, $label),
					'btn btn-default btn-sm',
					$editable
				),
			];

			// Delete with a confirmation modal (t3js-modal-trigger, cross-version)
			if ($editable && !$deleteDisabled) {
				$actions[] = sprintf(
					'<button type="button" class="btn btn-default btn-sm t3js-modal-trigger" title="%s" aria-label="%s"'
						. ' data-severity="warning" data-title="%s" data-content="%s"'
						. ' data-button-ok-text="%s" data-button-close-text="%s" data-uri="%s">%s</button>',
					htmlspecialchars($deleteLabel),
					htmlspecialchars($deleteLabel),
					htmlspecialchars($deleteLabel),
					htmlspecialchars(sprintf($languageService->sL(self::WEB_LIST_LABELS . 'deleteWarning'), $label)),
					htmlspecialchars($deleteLabel),
					htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:cancel')),
					htmlspecialchars($this->tceDbUrl([$table => [$uid => ['delete' => 1]]], $returnUrl)),
					$iconFactory->getIcon('actions-edit-delete', IconSize::SMALL)->render()
				);
			}

			// Drag handle (trailing) - initialised by @amdeu/shape/preview-form-sortable.js
			if ($sortable) {
				$actions[] = sprintf(
					'<button type="button" class="btn btn-default btn-sm shape-preview-drag-handle" title="%s" aria-label="%s" style="cursor:move">%s</button>',
					htmlspecialchars($dragTitle),
					htmlspecialchars($dragTitle),
					$iconFactory->getIcon('actions-move-move', IconSize::SMALL)->render()
				);
			}

			$groups[] = sprintf(
				'<span class="btn-group btn-group-sm shape-preview-sortable-item" role="group" data-uid="%d">%s</span>',
				$uid,
				implode('', $actions)
			);
		}

		// Trailing "add new" button, pre-linked to this form and inserted after the last record.
		// It lives in the same flex row as the records so it never wraps onto its own line;
		// preview-form-sortable.js keeps it pinned to the end while dragging.
		if ($canCreate) {
			$newTitle = $languageService->sL(TcaUtility::t($newTitleKey));
			$newTarget = $records === [] ? $storagePid : -(int)$records[$count - 1]['uid'];
			$newUrl = (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('record_edit', [
				'edit' => [$table => [$newTarget => 'new']],
				'defVals' => [$table => [$parentField => $formUid]],
				'returnUrl' => $returnUrl,
			]);
			$groups[] = sprintf(
				'<a class="btn btn-default btn-sm shape-preview-add" href="%s" title="%s" aria-label="%s">%s</a>',
				htmlspecialchars($newUrl),
				htmlspecialchars($newTitle),
				htmlspecialchars($newTitle),
				$iconFactory->getIcon('actions-plus', IconSize::SMALL)->render()
			);
		}

		if ($groups === []) {
			return '';
		}

		$sortableAttributes = $sortable
			? sprintf(' data-shape-preview-sortable data-table="%s" data-pid="%d"', htmlspecialchars($table), $storagePid)
			: '';

		return '<div class="d-flex flex-wrap gap-1"' . $sortableAttributes . '>' . implode('', $groups) . '</div>';
	}

	/**
	 * Edit link for a record.
	 *
	 * Not editable: a non-interactive, dimmed control (mirrors the list module, which
	 * still lists the record but without an edit link).
	 * v14: opens the contextual record-edit slide-in (custom element).
	 * v13: plain link to the regular edit form.
	 */
	protected function editTrigger(string $table, int $uid, string $returnUrl, string $inner, string $title, string $class, bool $editable): string
	{
		if (!$editable) {
			return '<span class="' . htmlspecialchars($class) . ' disabled" aria-disabled="true">' . $inner . '</span>';
		}

		$editUrl = $this->editUrl($table, $uid, $returnUrl);

		if (!$this->isV14()) {
			return sprintf(
				'<a class="%s" href="%s" title="%s" aria-label="%s">%s</a>',
				htmlspecialchars($class),
				htmlspecialchars($editUrl),
				htmlspecialchars($title),
				htmlspecialchars($title),
				$inner
			);
		}

		$this->loadContextualEditModule();
		$contextualUrl = (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('record_edit_contextual', [
			'edit' => [$table => [$uid => 'edit']],
			'returnUrl' => $returnUrl,
		]);

		return sprintf(
			'<typo3-backend-contextual-record-edit-trigger class="%s" url="%s" edit-url="%s" title="%s" aria-label="%s">%s</typo3-backend-contextual-record-edit-trigger>',
			htmlspecialchars($class),
			htmlspecialchars($contextualUrl),
			htmlspecialchars($editUrl),
			htmlspecialchars($title),
			htmlspecialchars($title),
			$inner
		);
	}

	protected function isV14(): bool
	{
		return $this->isV14 ??= TcaUtility::getTypo3Version()->getMajorVersion() >= 14;
	}

	/**
	 * Table-level write access on the given storage page: tables_modify plus the
	 * "edit content" page permission for the folder the records live in
	 * (mirrors DatabaseRecordList::isEditable() + ::canEditTable()).
	 */
	protected function canModifyTable(string $table, int $storagePid): bool
	{
		$backendUser = $this->getBackendUser();
		if (!$backendUser->check('tables_modify', $table)) {
			return false;
		}
		$pageRecord = $storagePid > 0 ? (BackendUtility::getRecord('pages', $storagePid) ?? []) : [];
		return (new Permission($backendUser->calcPerms($pageRecord)))->editContentPermissionIsGranted();
	}

	/**
	 * Record-level edit access: table/page permission plus the record-specific checks
	 * (editlock, language and type restrictions).
	 */
	protected function canEditRecord(string $table, array $record): bool
	{
		return $this->canModifyTable($table, (int)($record['pid'] ?? 0))
			&& $this->recordEditAccessAllowed($table, $record);
	}

	/**
	 * BackendUserAuthentication::checkRecordEditAccess() on v14 (recordEditAccessInternals()
	 * is deprecated there and emits a warning on every call), recordEditAccessInternals() on v13.
	 */
	protected function recordEditAccessAllowed(string $table, array $record): bool
	{
		$backendUser = $this->getBackendUser();
		if (method_exists($backendUser, 'checkRecordEditAccess')) {
			return $backendUser->checkRecordEditAccess($table, $record)->isAllowed;
		}
		return $backendUser->recordEditAccessInternals($table, $record);
	}

	protected function historyEnabled(string $table): bool
	{
		return (bool)trim($this->userTsOption('showHistory', $table, '1'));
	}

	protected function listModuleAccessible(): bool
	{
		// v14 renamed the list module "web_list" -> "records"; stored user permissions are migrated too.
		return $this->getBackendUser()->check('modules', $this->isV14() ? 'records' : 'web_list');
	}

	protected function userTsOption(string $key, string $table, string $default = ''): string
	{
		$this->userTsOptions ??= $this->getBackendUser()->getTSConfig()['options.'] ?? [];
		return (string)($this->userTsOptions[$key . '.'][$table] ?? $this->userTsOptions[$key] ?? $default);
	}

	protected function loadSortableModule(): void
	{
		if ($this->sortableModuleLoaded) {
			return;
		}
		$this->sortableModuleLoaded = true;
		GeneralUtility::makeInstance(PageRenderer::class)
			->loadJavaScriptModule('@amdeu/shape/preview-form-sortable.js');
	}

	protected function loadContextualEditModule(): void
	{
		if ($this->contextualEditModuleLoaded) {
			return;
		}
		$this->contextualEditModuleLoaded = true;
		GeneralUtility::makeInstance(PageRenderer::class)
			->loadJavaScriptModule('@typo3/backend/element/contextual-record-edit-trigger.js');
	}

	protected function tceDbUrl(array $cmd, string $returnUrl): string
	{
		return (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('tce_db', [
			'cmd' => $cmd,
			'redirect' => $returnUrl,
		]);
	}

	protected function editUrl(string $table, int $uid, string $returnUrl): string
	{
		return (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('record_edit', [
			'edit' => [$table => [$uid => 'edit']],
			'returnUrl' => $returnUrl,
		]);
	}

	protected function warning(string $message): string
	{
		return '<div class="badge badge-warning mt-2">' . htmlspecialchars($message) . '</div>';
	}

	/**
	 * v13: GridColumnItem::getRecord() returns the raw array.
	 * v14: GridColumnItem::getRecord() returns a RecordInterface, getRow() returns the raw array.
	 */
	protected function getContentRow(GridColumnItem $item): array
	{
		if (method_exists($item, 'getRow')) {
			return $item->getRow();
		}
		/** @phpstan-ignore-next-line v13 return type */
		return $item->getRecord();
	}

	protected function resolveFormUid(string $flexFormXml): int
	{
		if ($flexFormXml === '') {
			return 0;
		}
		$settings = GeneralUtility::makeInstance(FlexFormService::class)
			->convertFlexFormContentToArray($flexFormXml);
		$value = (string)($settings['settings']['form'] ?? '');

		// group fields may store the plain uid or a "table_uid" reference
		return preg_match('/(\d+)$/', $value, $matches) ? (int)$matches[1] : 0;
	}
}
