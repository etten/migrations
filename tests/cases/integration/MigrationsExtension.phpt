<?php

/**
 * @testCase
 */

namespace Etten\Migrations;

use Etten\Migrations\Bridges\NetteDI\MigrationsExtension;
use Nette;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

class MigrationsExtensionTest extends TestCase
{

	/**
	 * @dataProvider provideData
	 * @param string $config
	 */
	public function testExtension(string $config)
	{
		$dibiConfig = parse_ini_file(__DIR__ . '/../../drivers.ini', TRUE)['mysql'];

		$generator = function (Nette\DI\Compiler $compiler) use ($config, $dibiConfig) {
			$compiler->addExtension('migrations', new MigrationsExtension());
			$compiler->addConfig(['parameters' => ['dibiConfig' => $dibiConfig]]);
			$compiler->loadConfig(__DIR__ . "/MigrationsExtension.$config.neon");
		};

		$key = __FILE__ . ':' . __LINE__ . ':' . $config;

		$loader = new Nette\DI\ContainerLoader(TEMP_DIR);
		$className = $loader->load($generator, $key);

		/** @var Nette\DI\Container $dic */
		$dic = new $className;
		Assert::type('Nette\DI\Container', $dic);
		Assert::type('Etten\Migrations\Drivers\MySqlDriver', $dic->getByType('Etten\Migrations\IDriver'));
		Assert::count(4, $dic->findByType('Symfony\Component\Console\Command\Command'));
		Assert::count(4, $dic->findByTag('kdyby.console.command'));
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
