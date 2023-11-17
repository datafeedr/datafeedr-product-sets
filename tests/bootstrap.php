<?php
/**
 * PHPUnit bootstrap file
 */

class Datafeedr_Bootstrap
{

	/** @var Datafeedr_Bootstrap instance */
	protected static $instance = null;

	/** @var string Location of WordPress tests */
	public string $wp_tests_dir;

	/** @var string */
	public string $tests_dir;

	/** @var string */
	public string $project_dir;

	/**
	 * Get the single class instance
	 *
	 * @return Datafeedr_Bootstrap
	 */
	public static function instance() : Datafeedr_Bootstrap
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Setup the environment for testing with WooCommerce an WooCommerce Subscriptions
	 */
	public function __construct()
	{
		ini_set('display_errors', 'on');
		error_reporting(E_ALL);

		// Setup paths.
		$this->setup_paths();

		$_SERVER['REMOTE_ADDR'] = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
		$_SERVER['SERVER_NAME'] = (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : 'wp_test';

		// Load the WP testing environment.
		require_once $this->wp_tests_dir . '/includes/functions.php';

		// Load the WP testing environment.
		require_once $this->wp_tests_dir . '/includes/bootstrap.php';

		// Manually load the plugin being tested.
		tests_add_filter('muplugins_loaded', 'manually_load_plugin');

		// Load testing framework.
		// $this->includes();
	}

	/**
	 * Setup paths to WooCommerce and WooCommerce Subscriptions.
	 */
	public function setup_paths()
	{
		$this->tests_dir = dirname(dirname(__FILE__));
		echo 'tests_dir: ' . $this->tests_dir . PHP_EOL;
		$this->project_dir = dirname(dirname(__FILE__));
		echo 'project_dir: ' . $this->project_dir . PHP_EOL;
		$this->wp_tests_dir = getenv('WP_TESTS_DIR') ? getenv('WP_TESTS_DIR') : '/tmp/datafeedr-product-sets/wordpress-tests-lib/';
		echo 'wp_tests_dir: ' . $this->wp_tests_dir . PHP_EOL;
	}

	/**
	 * Manually load the plugin being tested.
	 */
	public function manually_load_plugin()
	{
		require dirname(dirname(__FILE__)) . '/datafeedr-product-sets.php';
	}

}

Datafeedr_Bootstrap::instance();
