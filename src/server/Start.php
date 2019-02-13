<?php
namespace AtServer\server;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

/**
 * Created by PhpStorm.
 * User: htpc
 * Date: 2018/8/7
 * Time: 15:44
 */

class Start
{
	public static function run()
	{
        $application = new Application();
        self::addDirCommand(CONSOLE_PATH,'\\AtServer\\console',$application);
        $application->run();
	}

	/**
	 * @param $root
	 * @param $namespace
	 * @param $application
	 */
	private static function addDirCommand($path, $namespace, Application $application)
	{
		if (!file_exists($path)) {
			return;
		}
		$file = scandir($path);
		foreach ($file as $value) {
			list($name, $ex) = explode('.', $value);
			if (!empty($name) && $ex == 'php') {
				$class = "$namespace\\$name";
				if (class_exists($class)) {
					$instance = new $class($name);
					if ($instance instanceof Command) {
						$application->add($instance);
					}
				}
			}
		}
	}
}