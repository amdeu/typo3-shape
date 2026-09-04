<?php

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Shape',
	'description' => 'Extensible and editor-friendly TYPO3 Form Extension',
	'author' => 'Amadeus Kiener',
	'author_email' => 'amd.kiener@gmail.com',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => '0',
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'version' => '0.4.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '13.4.0-14.99.99',
			'backend' => '13.4.0-14.99.99',
			'extbase' => '13.4.0-14.99.99',
			'fluid' => '13.4.0-14.99.99',
			'fluid_styled_content' => '13.4.0-14.99.99',
			'frontend' => '13.4.0-14.99.99',
			'install' => '13.4.0-14.99.99',
			'rte_ckeditor' => '13.4.0-14.99.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'scheduler' => '13.4.0-14.99.99',
		),
	),
);
