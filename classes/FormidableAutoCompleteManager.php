<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormidableAutoCompleteManager {

	protected $plugin_slug;
	private static $plugin_short = 'FormidableAutoComplete';
	protected static $version;

	public function __construct() {
		$this->plugin_slug = 'formidable-autocomplete';

		self::$version = '1.0';

		require_once 'FormidableAutoCompleteAdmin.php';
		$admin = new FormidableAutoCompleteAdmin();

		require_once 'FormidableAutoCompleteField.php';
		$field = new FormidableAutoCompleteField();

		require_once 'FormidableAutocompleteLogs.php';
		$log = new FormidableAutocompleteLogs();

		require_once 'FormidableAutoCompleteOption.php';
		$extra_options = new FormidableAutoCompleteOption();
	}

	static function getShort() {
		return self::$plugin_short;
	}

	static function getVersion() {
		return self::$version;
	}
}