<?php
// autoloader
spl_autoload_register(array(new Favicon_Autoloader(), 'autoload'));

if (!class_exists('\Favicon\Favicon'))
{
	trigger_error('Autoloader not registered properly', E_USER_ERROR);
}

/**
 * Autoloader class
 *
 * @package SimplePie
 * @subpackage API
 */
class Favicon_Autoloader
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->path = dirname(__FILE__) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Autoloader
	 *
	 * @param string $class The name of the class to attempt to load.
	 */
	public function autoload($class)
	{
		if (strpos($class, 'Favicon') !== 0)
		{
			return;
		}

        $parts = explode('\\', $class);
		include $this->path . end($parts) . '.php';
	}
}