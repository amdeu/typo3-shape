<?php

declare(strict_types=1);

namespace Amdeu\Shape\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Amdeu\Shape\Form;

// typo3 ext idea: new alternative renderType for inline and potentially file fields as a carousel. in many cases a slider is better to work with than a list of collapsed items

// todo: use CorePasswordValidator, passwordPolicy field for password fields
// todo: add labels for default browser validation messages, e.g. valueMissing, typeMismatch, etc.
// todo: extract validators into own extension, sth like html5_validators

// todo: FormReflection with things like fieldNames, finisher types, other field information
// todo: FormBuilder to build virtual forms, createFromYaml method
// todo: option to choose yaml instead of form record in plugin
// todo: add validators field to field model, at least in yaml files
// todo: FormPresets, presets to create predefined forms, fields, finishers etc, with configurable readonly fields (useful for tx_shape_field properties like 'name', or even to make everything but labels readonly)
// todo: extract into extensions: repeatable containers, fe_user prefill, unique validation, rate limiter, google recaptcha
// todo: delete/move uploads finisher?
// todo: rate limiter finisher?
// note: upload and radio fields will not be in POST values if no value is set

class FormController extends ActionController
{
	public function __construct(
		protected readonly Form\FormRuntimeFactory $runtimeFactory
	)
	{
	}

	protected Form\FormRuntime $runtime;
	protected int $fragmentPageTypeNum = 1761312405;

	/**
	 * Renders the static initial form page or the lazy loader
	 */
	public function renderAction(): ResponseInterface
	{
		$pageType = (int)($this->request->getQueryParams()['type'] ?? 0);
		if ($this->settings['lazyLoad'] && $pageType !== $this->fragmentPageTypeNum) {
			return $this->lazyLoader();
		}

		$this->initializeRuntime();
		return $this->formPage();
	}

	/**
	 * Handles the dynamic form process; page navigation, validation, processing and executing finishers
	 */
	public function runAction(int $pageIndex = 0): ResponseInterface
	{
		$this->initializeRuntime();
		if (!$this->runtime->isRequestedPlugin()) {
			return $this->formPage(messages: [Form\FormMessage::fromKey('label.not_requested_plugin', ContextualFeedbackSeverity::WARNING)]);
		}
		if (!$this->runtime->isFormPostRequest()) {
			return $this->formPage(messages: [Form\FormMessage::fromKey('label.not_form_post_request', ContextualFeedbackSeverity::INFO)]);
		}
		if ($this->runtime->findSpamReasons()) {
			return $this->formPage(messages: [Form\FormMessage::fromKey('label.suspected_spam', ContextualFeedbackSeverity::ERROR)]);
		}
		// pageIndex is 1-based
		// if pageIndex is 0, the form is being submitted
		if ($pageIndex) {
			$submittedPageIndex = $this->runtime->session->returnPageIndex;
			if (!$this->runtime->isStepBack) {
				$this->runtime->validatePage($submittedPageIndex);
			}
			$this->runtime->serializePage($submittedPageIndex);
			if ($this->runtime->getHasErrors()) {
				return $this->formPage($submittedPageIndex);
			}
			return $this->formPage($pageIndex);
		}
		$this->runtime->validateForm();
		$this->runtime->serializeForm();
		if ($this->runtime->getHasErrors()) {
			$firstPageWithErrors = $this->runtime->session->returnPageIndex;
			return $this->formPage($firstPageWithErrors);
		}
		$this->runtime->processForm();
		$finishResult = $this->runtime->finishForm();
		if ($this->runtime->getHasErrors()) {
			$firstPageWithErrors = $this->runtime->session->returnPageIndex;
			return $this->formPage($firstPageWithErrors);
		}
		if ($finishResult->response) {
			return $finishResult->response;
		}
		$nonce = bin2hex(random_bytes(16));
		$feAuth = $this->request->getAttribute('frontend.user');
		$feAuth->setAndSaveSessionData('tx_shape_finish_' . $nonce, [
			'template' => $finishResult->finishedTemplate,
			'variables' => $finishResult->finishedVariables,
			'formValues' => $this->runtime->session->values,
		]);
		return $this->redirect('finished', arguments: ['finishToken' => $nonce]);
	}

	/**
	 * Renders view after form is finished
	 */
	public function finishedAction(string $finishToken = ''): ResponseInterface
	{
		$this->initializeRuntime();
		if (!$this->runtime->isRequestedPlugin()) {
			return $this->formPage();
		}
		$variables = [
			'plugin' => $this->runtime->plugin,
			'form' => $this->runtime->form,
			'settings' => $this->settings,
		];
		$template = '';
		if ($finishToken) {
			$feAuth = $this->request->getAttribute('frontend.user');
			$sessionKey = 'tx_shape_finish_' . $finishToken;
			$finishContext = $feAuth->getSessionData($sessionKey);
			if ($finishContext) {
				$template = $finishContext['template'] ?? '';
				$variables = array_merge($variables, $finishContext['variables'] ?? []);
				$variables['formValues'] = $finishContext['formValues'] ?? [];
			}
		}
		$this->view->assignMultiple($variables);
		return $this->htmlResponse($this->view->render($template));
	}

	protected function initializeRuntime(): void
	{
		$this->runtime = $this->runtimeFactory
			->createFromRequest($this->request, $this->view, $this->settings);
	}
	/**
	 * @param Form\FormMessage[] $messages
	 */
	protected function formPage(int $pageIndex = 1, array $messages = []): ResponseInterface
	{
		if ($messages) {
			$this->runtime->addMessages($messages);
		}
		return $this->htmlResponse($this->runtime->renderPage($pageIndex));
	}
	public function lazyLoader(): ResponseInterface
	{
		$contentData = $this->request->getAttribute('currentContentObject')?->data;
		$uri = $this->uriBuilder
			->reset()
			->setNoCache(true)
			->setCreateAbsoluteUri(true)
			->setTargetPageType($this->fragmentPageTypeNum)
			->setTargetPageUid($this->request->getAttribute('routing')->getPageId())
			->setArguments(['ceUid' => $contentData['uid']])
			->uriFor('render');
		$this->view->assign('fetchUri', $uri);
		return $this->htmlResponse(
			$this->view->render('FormLazyLoader')
		);
	}
}
