<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations\Bridges\NetteDI;

use Etten;
use Nette;
use Nette\Utils\Validators;

class MigrationsExtension extends Nette\DI\CompilerExtension
{

	/** @var array */
	public $defaults = [
		'dir' => NULL,
		'phpParams' => [],
		'driver' => NULL,
		'dbal' => NULL,
		'handlers' => [],
	];

	/** @var array */
	protected $dbals = [
		'dibi' => 'Etten\Migrations\Bridges\Dibi\DibiAdapter',
		'doctrine' => 'Etten\Migrations\Bridges\DoctrineDbal\DoctrineAdapter',
		'nette' => 'Etten\Migrations\Bridges\NetteDatabase\NetteAdapter',
	];

	/** @var array */
	protected $drivers = [
		'mysql' => 'Etten\Migrations\Drivers\MySqlDriver',
		'pgsql' => 'Etten\Migrations\Drivers\PgSqlDriver',
	];

	/**
	 * Processes configuration data. Intended to be overridden by descendant.
	 * @return void
	 */
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);
		Validators::assertField($config, 'dir', 'string');
		Validators::assertField($config, 'phpParams', 'array');
		Validators::assertField($config, 'handlers', 'array');

		$dbal = $this->getDbal($config['dbal']);
		$driver = $this->getDriver($config['driver'], $dbal);

		$handlers = [];
		$handlers['sql'] = $builder->addDefinition($this->prefix('sqlHandler'))
			->setClass('Etten\Migrations\Extensions\SqlHandler')
			->setArguments([$driver]);
		$handlers['php'] = $builder->addDefinition($this->prefix('phpHandler'))
			->setClass('Etten\Migrations\Extensions\PhpHandler')
			->setArguments($config['phpParams']);

		foreach ($config['handlers'] as $extension => $handler) {
			$handlers[$extension] = $handler;
		}

		$params = [$driver, $config['dir'], $handlers];
		$builder->addExcludedClasses(['Etten\Migrations\Bridges\SymfonyConsole\BaseCommand']);

		$builder->addDefinition($this->prefix('continueCommand'))
			->setClass('Etten\Migrations\Bridges\SymfonyConsole\ContinueCommand')
			->setArguments($params)
			->addTag('kdyby.console.command');

		$builder->addDefinition($this->prefix('createCommand'))
			->setClass('Etten\Migrations\Bridges\SymfonyConsole\CreateCommand')
			->setArguments($params)
			->addTag('kdyby.console.command');

		$builder->addDefinition($this->prefix('resetCommand'))
			->setClass('Etten\Migrations\Bridges\SymfonyConsole\ResetCommand')
			->setArguments($params)
			->addTag('kdyby.console.command');
	}

	private function getDriver($driver, $dbal)
	{
		$factory = $this->getDriverFactory($driver, $dbal);

		if ($factory) {
			return $this->getContainerBuilder()
				->addDefinition($this->prefix('driver'))
				->setClass('Etten\Migrations\IDriver')
				->setFactory($factory);

		} elseif ($driver === NULL) {
			return '@Etten\Migrations\IDriver';

		} else {
			throw new Etten\Migrations\LogicException('Invalid driver value.');
		}
	}

	private function getDriverFactory($driver, $dbal)
	{
		if ($driver instanceof Nette\DI\Statement) {
			return Nette\DI\Compiler::filterArguments([$driver])[0];

		} elseif (is_string($driver) && isset($this->drivers[$driver])) {
			return new Nette\DI\Statement($this->drivers[$driver], [$dbal]);

		} else {
			return NULL;
		}
	}

	private function getDbal($dbal)
	{
		$factory = $this->getDbalFactory($dbal);

		if ($factory) {
			return $this->getContainerBuilder()
				->addDefinition($this->prefix('dbal'))
				->setClass('Etten\Migrations\IDbal')
				->setFactory($factory);

		} elseif ($dbal === NULL) {
			return '@Etten\Migrations\IDbal';

		} else {
			throw new Etten\Migrations\LogicException('Invalid dbal value');
		}
	}

	private function getDbalFactory($dbal)
	{
		if ($dbal instanceof Nette\DI\Statement) {
			return Nette\DI\Compiler::filterArguments([$dbal])[0];

		} elseif (is_string($dbal) && isset($this->dbals[$dbal])) {
			return $this->dbals[$dbal];

		} else {
			return NULL;
		}
	}

}
