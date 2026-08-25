<?php

namespace Amdeu\Shape\Form;

use TYPO3\CMS\Core;
use TYPO3\CMS\Extbase;

class FormRuntime
{
	public function __construct(
		readonly protected Core\EventDispatcher\EventDispatcher $eventDispatcher,
		readonly protected Module\ModuleInvoker                	$moduleInvoker,
		readonly protected Condition\FieldConditionResolver     $fieldConditionResolver,
		readonly protected Processing\FieldValueProcessor       $fieldValueProcessor,
		readonly protected Serialization\FieldValueSerializer   $fieldValueSerializer,
		readonly protected Validation\FieldValueValidator       $fieldValueValidator,
		readonly public Extbase\Mvc\RequestInterface			$request,
		readonly public array                                   $settings,
		readonly public Core\View\ViewInterface                 $view,
		readonly public Core\Domain\Record                      $plugin,
		readonly public Model\FormInterface                     $form,
		readonly public FormSession                             $session,
		readonly public array                                   $postValues,
		readonly public Core\Resource\ResourceStorageInterface  $uploadStorage,
		readonly public string                                  $parsedBodyKey,
		readonly public bool                                    $isStepBack = false,
		protected ?array                                        $spamReasons = null,
		protected array                                         $messages = [],
		protected bool                                          $hasErrors = false,
	)
	{
		$this->moduleInvoker->initializeModules($this);
		$this->setFieldSessionValues();
		$event = new FormRuntimeCreationEvent($this);
		$this->eventDispatcher->dispatch($event);
	}

	/**
	 * Sets the session values of all form fields from the form session
	 */
	public function setFieldSessionValues(): self
	{
		foreach ($this->form->getPages() as $page) {
			foreach ($page->getFields() as $field) {
				if ($field->isFormControl()) {
					$field->setSessionValue($this->session->values[$field->getName()] ?? null);
				}
			}
		}
		return $this;
	}

	/**
	 * Analyzes the form submission for spam indicators by dispatching a SpamAnalysisEvent
	 */
	public function findSpamReasons(): array
	{
		$event = new SpamProtection\SpamAnalysisEvent($this);
		$this->eventDispatcher->dispatch($event);
		$this->spamReasons = $event->spamReasons;
		return $this->spamReasons;
	}

	/**
	 * Checks if the current request is intended for this plugin instance; necessary in case of multiple plugins on one page
	 */
	public function isRequestedPlugin(): bool
	{
		if (!isset($this->request->getArguments()['pluginUid'])) {
			return true;
		}
		$uid = $this->settings['pluginUid'] ?: $this->request->getAttribute('currentContentObject')?->data['uid'] ?? $this->request->getArguments()['pluginUid'] ?? null;
		return $this->request->getArguments()['pluginUid'] == $uid;
	}

	/**
	 * Checks if the current request is a form POST request for this form
	 */
	public function isFormPostRequest(): bool
	{
		return $this->request->getMethod() === 'POST' && array_key_exists($this->parsedBodyKey, $this->request->getParsedBody());
	}

	/**
	 * Adds messages to be displayed on the form page
	 * @param FormMessage[] $messages
	 */
	public function addMessages(array $messages): void
	{
		$this->messages = array_merge($this->messages, $messages);
	}

	/**
	 * Renders the form page for the given page index
	 * Resolves field display conditions before rendering
	 */
	public function renderPage(int $pageIndex = 1): string
	{
		$pages = $this->form->getPages();
		$lastPageIndex = count($pages);
		$currentPageRecord = $pages[$pageIndex - 1];
		$this->session->returnPageIndex = $pageIndex;

		// Resolve display conditions with "stepType" of page to be rendered
		$expressionResolver = $this->createExpressionResolver(['stepType' => $currentPageRecord->get('type')]);
		foreach ($pages as $page) {
			foreach ($page->getFields() as $field) {
				$field->setConditionResult($this->fieldConditionResolver->evaluate($this, $field, $expressionResolver));
			}
		}

		$viewVariables = [
			'session' => $this->session,
			'serializedSession' => FormSession::serialize($this->session),
			'namespace' => $this->form->getName(),
			'action' => 'run',
			'plugin' => $this->plugin,
			'form' => $this->form,
			'settings' => $this->settings,
			'messages' => $this->messages,
			'spamReasons' => $this->spamReasons,
			'currentPage' => $currentPageRecord,
			'pageIndex' => $pageIndex,
			'isFirstPage' => $pageIndex === 1,
			'isLastPage' => $pageIndex === $lastPageIndex,
			'backStepPageIndex' => $pageIndex - 1 ?: null,
			'forwardStepPageIndex' => $lastPageIndex === $pageIndex ? null : $pageIndex + 1,
		];

		$event = new Rendering\BeforeFormRenderEvent($this, $viewVariables);
		$this->eventDispatcher->dispatch($event);
		$viewVariables = $event->getVariables();

		$this->view->assignMultiple($viewVariables);
		return $this->view->render('Form');
	}

	/**
	 * Validates all fields on the given page index
	 */
	public function validatePage(int $pageIndex): void
	{
		$page = $this->form->getPages()[$pageIndex - 1] ?? null;
		if (!$page || !$page->has('fields')) {
			return;
		}

		// Resolve display conditions with "stepType" of the page fields are on, necessary before validation for required fields
		$expressionResolver = $this->createExpressionResolver(['stepType' => $page->get('type')]);
		foreach ($page->getFields() as $field) {
			$field->setConditionResult($this->fieldConditionResolver->evaluate($this, $field, $expressionResolver));
			$field->setValidationResult($this->fieldValueValidator->validate($this, $field, $this->getFieldValue($field)));
			if ($field->getValidationResult()->hasErrors()) {
				$this->hasErrors = true;
			}
		}
	}

	/**
	 * Serializes all field values on the given page index
	 */
	public function serializePage(int $pageIndex): void
	{
		$page = $this->form->getPages()[$pageIndex - 1] ?? null;
		if (!$page || !$page->has('fields')) {
			return;
		}
		foreach ($page->getFields() as $field) {
			if (!$field->isFormControl()) {
				continue;
			}
			$serializedValue = $this->fieldValueSerializer->serialize($this, $field, $this->getFieldValue($field));
			$this->setFieldValue($field, $serializedValue);
		}
	}

	/**
	 * Validates all pages and their fields in the form
	 * Stops at the first page with validation errors
	 */
	public function validateForm(): void
	{
		foreach ($this->form->getPages() as $index => $page) {
			$pageIndex = $index + 1;
			$this->validatePage($pageIndex);
			if ($this->hasErrors) {
				$this->session->returnPageIndex = $pageIndex;
				break;
			}
		}
	}

	/**
	 * Serializes all pages and their fields in the form
	 */
	public function serializeForm(): void
	{
		foreach ($this->form->getPages() as $index => $page) {
			$this->serializePage($index + 1);
		}
	}

	/**
	 * Processes all field values in the form
	 */
	public function processForm(): void
	{
		foreach ($this->form->getPages() as $page) {
			foreach ($page->getFields() as $field) {
				if (!$field->isFormControl()) {
					continue;
				}
				$processedValue = $this->fieldValueProcessor->process($this, $field, $this->getFieldValue($field));
				$this->setFieldValue($field, $processedValue);
			}
		}
	}

	/**
	 * Dispatches a FormFinishEvent to invoke all configured modules listening on form finish.
	 * Modules can call stopPropagation() on the event to prevent subsequent modules from executing.
	 */
	public function finishForm(array $conditionVariables = []): FormFinishEvent
	{
		$finishEvent = new FormFinishEvent($this, conditionVariables: $conditionVariables);
		$this->eventDispatcher->dispatch($finishEvent);
		return $finishEvent;
	}

	/**
	 * Creates an expression resolver with the given additional variables
	 */
	public function createExpressionResolver(array $variables = []): Core\ExpressionLanguage\Resolver
	{
		$variables = array_merge([
			'formRuntime' => $this,
			'formValues' => $this->session->values,
			'request' => new Core\ExpressionLanguage\RequestWrapper($this->request),
			'site' => $this->request->getAttribute('site'),
			'frontendUser' => $this->request->getAttribute('frontend.user'),
		], $variables);
		$event = new Condition\ExpressionResolverCreationEvent($this, $variables);
		$this->eventDispatcher->dispatch($event);
		return Core\Utility\GeneralUtility::makeInstance(
			Core\ExpressionLanguage\Resolver::class,
			'tx_shape', $event->getVariables()
		);
	}

	/**
	 * Gets the value of a field from the form session
	 */
	public function getFieldValue(Model\FieldInterface $field): mixed
	{
		return $this->session->values[$field->getName()] ?? null;
	}

	/**
	 * Sets the value of a field in the form session
	 */
	public function setFieldValue(Model\FieldInterface $field, mixed $value): void
	{
		$field->setSessionValue($value);
		$name = $field->getName();
		$this->session->values[$name] = $value;
		if (isset($this->session->values[$name . '__CONFIRM'])) {
			$this->session->values[$name . '__CONFIRM'] = $value;
		}
	}

	/**
	 * Returns whether the form runtime has validation errors
	 */
	public function getHasErrors(): bool
	{
		return $this->hasErrors;
	}

	/**
	 * Returns the upload folder path for the current session
	 */
	public function getSessionUploadFolder(): string
	{
		return explode(':', $this->settings['uploadFolder'])[1] . $this->session->getId() . '/';
	}
}