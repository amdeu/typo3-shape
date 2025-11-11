<?php

namespace Amdeu\Shape\Form\Rendering;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use Amdeu\Shape\Repository;

final class UserDataFieldPrefiller
{
	public function __construct(
		protected Repository\GenericRepositoryFactory $genericRepositoryFactory,
	)
	{
	}

	#[AsEventListener]
	public function __invoke(BeforeFormRenderEvent $event): void
	{
		$feAuth = $event->runtime->request->getAttribute('frontend.user');
		if (!$feAuth->getUserId()) {
			return;
		}
		$repository = $this->genericRepositoryFactory
			->forTable('fe_users')
			->reset()
			->setReturnRawQueryResult(true);

		$user = $repository->findByUid($feAuth->getUserId());
		if (!$user) {
			return;
		}

		foreach ($event->runtime->form->getPages() as $page) {
			foreach ($page->getFields() as $field) {
				if (!$field->has('user_prefill_column')) {
					continue;
				}
				$prefillColumn = $field->get('user_prefill_column');
				if ($prefillColumn && isset($user[$prefillColumn])) {
					$field->runtimeOverride('default_value', $user[$prefillColumn]);
				}
			}
		}
	}
}