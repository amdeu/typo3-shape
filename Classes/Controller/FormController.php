<?php

declare(strict_types=1);

namespace Amdeu\Shape\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Amdeu\Shape\Form;

// todo: FormReflection with things like fieldNames, finisher types, other field information
// todo: FormBuilder to build virtual forms, createFromYaml method
// todo: option to choose yaml instead of form record in plugin
// todo: add validators field to field model, at least in yaml files
// todo: FormPresets, presets to create predefined forms, fields, finishers etc, with configurable readonly fields (useful for tx_shape_field properties like 'name', or even to make everything but labels readonly)
// todo: extract into extensions: repeatable containers, fe_user prefill, unique validation, rate limiter, google recaptcha
// todo: delete/move uploads finisher?
// todo: rate limiter finisher?
// todo: all settings for plugin: disable server validation?,
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

	public function renderAction(): ResponseInterface
	{
		$pageType = (int)($this->request->getQueryParams()['type'] ?? 0);
		if ($this->settings['lazyLoad'] && $pageType !== $this->fragmentPageTypeNum) {
			return $this->lazyLoader();
		}

		$this->initializeRuntime();
		return $this->formPage();
	}

	public function runAction(int $pageIndex = 0): ResponseInterface
	{
		$this->initializeRuntime();
		if (!$this->runtime->isRequestedPlugin()) {
			return $this->formPage(messages: [['key' => 'label.not_requested_plugin', 'type' => 'warning']]);
		}
		if (!$this->runtime->isFormPostRequest()) {
			return $this->formPage(messages: [['key' => 'label.not_form_post_request', 'type' => 'info']]);
		}
		if ($this->runtime->findSpamReasons()) {
			return $this->formPage(messages: [['key' => 'label.suspected_spam', 'type' => 'error']]);
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
		return $finishResult->response ?? $this->redirect('finished', arguments: $finishResult->finishedActionArguments);
	}

	public function finishedAction(): ResponseInterface
	{
		$this->initializeRuntime();
		if (!$this->runtime->isRequestedPlugin()) {
			return $this->formPage();
		}
		$arguments = $this->request->getArguments();
		$variables = [
			'plugin' => $this->runtime->plugin,
			'form' => $this->runtime->form,
			'settings' => $this->settings,
			'arguments' => $arguments,
		];
		$this->view->assignMultiple($variables);
		return $this->htmlResponse(
			$this->view->render($arguments['template'] ?? '')
		);
	}

	protected function initializeRuntime(): void
	{
		$this->runtime = $this->runtimeFactory
			->createFromRequest($this->request, $this->view, $this->settings);
	}
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
