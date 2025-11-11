<?php

namespace Amdeu\Shape\Form;

use TYPO3\CMS\Core;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use Amdeu\Shape\Repository;

class FormRuntimeFactory implements FormRuntimeFactoryInterface
{
	public function __construct(
		protected readonly Core\EventDispatcher\EventDispatcher                $eventDispatcher,
		protected readonly Core\Resource\StorageRepository                     $storageRepository,
		protected readonly Core\Service\FlexFormService                        $flexFormService,
		protected readonly Extbase\Configuration\ConfigurationManagerInterface $configurationManager,
		protected readonly ContentObjectRenderer                               $contentObject,
		protected readonly Condition\FieldConditionResolver                    $fieldConditionResolver,
		protected readonly Processing\FieldValueProcessor                      $fieldValueProcessor,
		protected readonly Serialization\FieldValueSerializer                  $fieldValueSerializer,
		protected readonly Validation\FieldValueValidator                      $fieldValueValidator,
		protected readonly Repository\ContentRepository                        $contentRepository,
	) {}

	// todo: make it work with any type of request (not only Extbase)?
	public function createFromRequest(
		Extbase\Mvc\RequestInterface $request,
		Core\View\ViewInterface $view,
		array $settings = [
			'pluginUid' => 0,
			'uploadFolder' => '1:/user_upload/',
		]
	): FormRuntime
	{
		$plugin = $this->getPluginRecord($request, $settings);
		$form = $this->getForm($request, $settings, $plugin);

		$uploadStorage = $this->storageRepository->findByCombinedIdentifier($settings['uploadFolder']);
		$parsedBodyKey = 'tx_shape_form';
		$cleanedPostValues = [];

		if (
			($request->getParsedBody()[$parsedBodyKey] ?? false)
			&& $request->getArguments()['pluginUid'] == $plugin->getUid()
		) {
			$postBody = $request->getParsedBody()[$parsedBodyKey];

			// unserialize session from post body or create new session
			$serializedSessionWithHmac = $postBody['__session'] ?? null;
			if (!$serializedSessionWithHmac) {
				$session = new FormSession();
			} else {
				try {
					$session = FormSession::validateAndUnserialize($serializedSessionWithHmac);
				} catch (Exception\InvalidSessionException  $e) {
					// todo: log invalid session
					//$logger->warning('Invalid session detected', ['exception' => $e]);
					$session = new FormSession();
				}
			}
			$session->getId();

			// manually create values form parsed body and uploaded files because getArguments() uses array_merge_recursive to merge parsed body and uploaded files, which is not what we want
			$postValues = $postBody[$form->get('name')] ?? [];
			Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
				$postValues,
				$request->getUploadedFiles()[$form->get('name')] ?? [],
			);

			// only keep post values that can be mapped to fields
			// substitute missing values with proxy values, if possible (e.g. for file fields)
			foreach ($form->getPages() as $page) {
				foreach ($page->getFields() as $field) {
					if (!$field->isFormControl()) {
						continue;
					}
					$name = $field->getName();
					if (isset($postValues[$name.'__CONFIRM'])) {
						$cleanedPostValues[$name.'__CONFIRM'] = $postValues[$name.'__CONFIRM'];
					}
					if (isset($postValues[$name])) {
						$cleanedPostValues[$name] = $postValues[$name];
					} else if (isset($postValues[$name.'__PROXY'])) {
						$cleanedPostValues[$name] = $postValues[$name.'__PROXY'];
					}
				}
			}
			$session->values = array_merge(
				$session->values,
				$cleanedPostValues
			);

			$pageIndex = $request->getArguments()['pageIndex'] ?? 1;
			$isStepBack = $session->returnPageIndex > $pageIndex;

		} else {
			$session = new FormSession();
			$isStepBack = false;
		}

		return new FormRuntime(
			$this->eventDispatcher,
			$this->flexFormService,
			$this->fieldConditionResolver,
			$this->fieldValueProcessor,
			$this->fieldValueSerializer,
			$this->fieldValueValidator,
			$request,
			$settings,
			$view,
			$plugin,
			$form,
			$session,
			$cleanedPostValues,
			$uploadStorage,
			$parsedBodyKey,
			$isStepBack,
			null,
			[],
			false,
		);
	}

	public function recreateFromRequestAndConsent(
		Extbase\Mvc\RequestInterface $request,
		Core\View\ViewInterface $view,
		array $consent
	): FormRuntime
	{
		$plugin = $this->contentRepository
			->reset()
			->findByUid($consent['plugin']);

		// recreate request
		$requestClone = clone $request;
		$this->contentObject->setRequest($requestClone);
		$this->contentObject->start($plugin->getRawRecord()->toArray(), 'tt_content');
		$requestClone = $requestClone->withAttribute('currentContentObject', $this->contentObject);

		// recreate session
		try {
			$session = FormSession::validateAndUnserialize($consent['session']);
		} catch (Exception\InvalidSessionException $e) {
			throw new \InvalidArgumentException('Could not recreate FormRuntime: Invalid session in consent.', 1741369823, $e);
		}

		// get plugin configuration
		$this->configurationManager->setRequest($requestClone);
		$this->configurationManager->setConfiguration(['extensionName' => 'Shape', 'pluginName' => 'Form']);
		$formPluginConfiguration = $this->configurationManager->getConfiguration(
			Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
			'Shape',
			'Form'
		);
		$settings = $formPluginConfiguration['settings'];

		// recreate view
		$viewClone = clone $view;

		/**
		 * $view->getRenderingContext() ties us to the FluidView implementation
		 * manipulating the root paths here is only necessary for finishers that render Fluid templates or need access to template paths
		 * For the current implementation (with Consent) to work with, for example, a JSON view, one would have to implement a different FormRuntimeFactory and Form-/ConsentController (because the controllers return html responses)
		 * the view problem for headless could also be solved by rendering json in the fluid templates
		 * the response type problem in controllers could be resolved by adding a request argument to determine the response type
		 * maybe make Finishers work with their own view instance via ViewFactoryInterface instead of the runtime view? => would remove the need to change the view here, make them view implementation agnostic and in combination with the json request argument allow headless usage
		 * simpler solution would be to only add the json request argument and manipulate the rendering via events, adding a json variable to the fluid template
		 */
		$viewClone->getRenderingContext()->setControllerName('Form');
		$viewClone->getRenderingContext()->getTemplatePaths()->setTemplateRootPaths($formPluginConfiguration['view']['templateRootPaths']);
		$viewClone->getRenderingContext()->getTemplatePaths()->setPartialRootPaths($formPluginConfiguration['view']['partialRootPaths']);
		$viewClone->getRenderingContext()->getTemplatePaths()->setLayoutRootPaths($formPluginConfiguration['view']['layoutRootPaths']);

		$form = $this->getForm($request, $settings, $plugin);

		$uploadStorage = $this->storageRepository->findByCombinedIdentifier($settings['uploadFolder']);
		$parsedBodyKey = 'tx_shape_form';

		// todo: BeforeFormRuntimeRecreationEvent to change request
		return new FormRuntime(
			$this->eventDispatcher,
			$this->flexFormService,
			$this->fieldConditionResolver,
			$this->fieldValueProcessor,
			$this->fieldValueSerializer,
			$this->fieldValueValidator,
			$requestClone,
			$settings,
			$viewClone,
			$plugin,
			$form,
			$session,
			[],
			$uploadStorage,
			$parsedBodyKey,
			false,
			null,
			[],
			false,
		);
	}

	protected function getPluginRecord(
		Extbase\Mvc\RequestInterface $request,
		array $settings
	): Core\Domain\Record
	{
		$uid = $settings['pluginUid'];
		if (!$uid) {
			if ($request->getAttribute('currentContentObject')?->data['CType']) {
				return Core\Utility\GeneralUtility::makeInstance(Core\Domain\RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $request->getAttribute('currentContentObject')?->data);
			}
			$uid = $request->getArguments()['pluginUid'] ?? 0;
		}
		$record = $this->contentRepository->findByUid($uid);
		if (!$record) {
			throw new \InvalidArgumentException('Could not resolve plugin content element from arguments "request" and "settings".', 1741369824);
		}
		return $record;
	}

	public function getForm(
		Extbase\Mvc\RequestInterface $request,
		array $settings,
		Core\Domain\Record $pluginRecord
	): Model\FormInterface
	{
		$event = new FormCreationEvent(
			$request, $settings, $pluginRecord
		);
		$this->eventDispatcher->dispatch($event);
		if ($event->isPropagationStopped()) {
			return $event->form;
		}
		// typo3 13.4.x version dependant
		$flexFormValues = $pluginRecord->get('pi_flexform');
		if (property_exists($flexFormValues, 'sheets')) {
			$formValue = $flexFormValues->get('general/settings')['form'] ?? null;
		} else {
			$formValue = $flexFormValues->get('settings')['form'] ?? null;
		}
		$form = $formValue[0] ?? $formValue;
		if (!$form instanceof Model\FormInterface) {
			throw new Exception\InvalidFormPluginRecordException('Plugin record (uid: '. $pluginRecord->getUid() .')  does not contain a valid FormInterface.', 1741369825);
		}
		return $form;
	}
}