<?php

// Exit if accessed directly

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array(
		'lottiplayer-script',
		'wp-blocks',
		'wp-block-editor',
		'wp-element',
		'wp-components',
		'wp-i18n',
	),
	'version'      => defined( 'WPLOTI_VERSION' ) ? WPLOTI_VERSION : '1.0.0',
);
