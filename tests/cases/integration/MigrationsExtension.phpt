<?php

/**
 * @testCase
 */

namespace Etten\Migrations;

use Etten\Migrations\Bridges\NetteDI\MigrationsExtension;
use Nette;
use Tester;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

class MigrationsExtensionTest extends TestCase
{
	/**
	 * @dataProvider provideData
	 */
	public function testExtension($config)
	{
		$dibiConfig = parse_ini_file(__DIR__ . '/../../drivers.ini', TRUE)['mysql'];

		$loader = new Nette\DI\ContainerLoader(TEMP_DIR);
		$key = __FILE__ . ':' . __LINE__ . ':' . $config;
		$className = $loader->load($key, function (Nette\DI\Compiler $compiler) use ($config, $dibiConfig) {
			$compiler->addExtension('migrations', new MigrationsExtension());
			$compiler->addConfig(['parameters' => ['dibiConfig' => $dibiConfig]]);
			$compiler->loadConfig(__DIR__ . "/MigrationsExtension.$config.neon");
		});

		/** @var Nette\DI\Container $dic */
		$dic = new $className;
		Assert::type('Nette\DI\Container', $dic);
		Assert::type('Etten\Migrations\Drivers\MySqlDriver', $dic->getByType('Etten\Migrations\IDriver'));
		Assert::count(3, $dic->findByType('Symfony\Component\Console\Command\Command'));
		Assert::count(3, $dic->findByTag('kdyby.console.command'));
	}

	public function provideData()
	{
		return [
			['configA'],
			['configB'],
			['configC'],
			['configD'],
			['configE'],
			['configF'],
		];
	}
}

(new MigrationsExtensionTest)->run();
