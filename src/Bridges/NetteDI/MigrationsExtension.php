<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations\Bridges\NetteDI;

use Etten;
use Nette\DI;
use Nette\Utils\Validators;

class MigrationsExtension extends DI\CompilerExtension
{

	/** @var array */
	public $defaults = [
		'groups' => [],
		'driver' => NULL,
		'dbal' => NULL,
		'handlers' => [],
		'php' => [
			'params' => [
				'container' => '@Nette\DI\Container',
			],
			'before' => [],
			'after' => [],
		],
	];

	/** @var array */
	protected $dbals = [
		'dibi' => Etten\Migrations\Bridges\Dibi\DibiAdapter::class,
		'doctrine' => Etten\Migrations\Bridges\DoctrineDbal\DoctrineAdapter::class,
		'nette' => Etten\Migrations\Bridges\NetteDatabase\NetteAdapter::class,
	];

	/** @var array */
	protected $drivers = [
		'mysql' => Etten\Migrations\Drivers\MySqlDriver::class,
		'pgsql' => Etten\Migrations\Drivers\PgSqlDriver::class,
	];

	/**
	 * Processes configuration data. Intended to be overridden by descendant.
	 * @return void
	 */
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$config = $this->validateConfig($this->defaults);
		$config['groups'] += $this->loadParametersGroups();

		Validators::assertField($config, 'groups', 'array');
		Validators::assertField($config, 'handlers', 'array');

		$dbal = $this->getDbal($config['dbal']);
		$driver = $this->getDriver($config['driver'], $dbal);

		$handlers = [];
		$handlers['sql'] = $builder->addDefinition($this->prefix('sqlHandler'))
			->setClass(Etten\Migrations\Extensions\SqlHandler::class)
			->setArguments([$driver]);

		$handlers['php'] = $builder->addDefinition($this->prefix('phpHandler'))
			->setClass(Etten\Migrations\Extensions\PhpHandler::class)
			->setArguments([$config['php']['params'], $config['php']['before'], $config['php']['after']]);

		foreach ($config['handlers'] as $extension => $handler) {
			$handlers[$extension] = $handler;
		}

		$params = [
			$driver,
			$config['groups'],
			$handlers,
		];

		$builder->addDefinition($this->prefix('continueCommand'))
			->setClass(Etten\Migrations\Bridges\SymfonyConsole\ContinueCommand::class)
			->setArguments($params)
			->addTag('kdyby.console.command');

		$builder->addDefinition($this->prefix('createCommand'))
			->setClass(Etten\Migrations\Bridges\SymfonyConsole\CreateCommand::class)
			->setArguments($params)
			->addTag('kdyby.console.command');

		$builder->addDefinition($this->prefix('resetCommand'))
			->setClass(Etten\Migrations\Bridges\SymfonyConsole\ResetCommand::class)
			->setArguments($params)
			->addTag('kdyby.console.command');

		$builder->addDefinition($this->prefix('initCommand'))
			->setClass(Etten\Migrations\Bridges\SymfonyConsole\InitCommand::class)
			->setArguments($params)
			->addTag('kdyby.console.command');
	}

	private function loadParametersGroups()
	{
		$builder = $this->getContainerBuilder();

		$groups = [];
		if (isset($builder->parameters['migrations']) && is_array($builder->parameters['migrations'])) {
			foreach ($builder->parameters['migrations'] as $name => $data) {
				$directory = array_shift($data);
				$dependencies = $data;

				$groups[$name] = [
					'directory' => $directory,
					'dependencies' => $dependencies,
				];
			}
		}

		return $groups;
	}

	private function getDriver($driver, $dbal)
	{
		$factory = $this->getDriverFactory($driver, $dbal);

		if ($factory) {
			return $this->getContainerBuilder()
				->addDefinition($this->prefix('driver'))
				->setClass(Etten\Migrations\IDriver::class)
				->setFactory($factory);

		} elseif ($driver === NULL) {
			return '@Etten\Migrations\IDriver';

		} else {
			throw new Etten\Migrations\LogicException('Invalid driver value.');
		}
	}

	private function getDriverFactory($driver, $dbal)
	{
		if ($driver instanceof DI\Statement) {
			return DI\Compiler::filterArguments([$driver])[0];

		} elseif (is_string($driver) && isset($this->drivers[$driver])) {
			return new DI\Statement($this->drivers[$driver], [$dbal]);

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
				->setClass(Etten\Migrations\IDbal::class)
				->setFactory($factory);

		} elseif ($dbal === NULL) {
			return '@Etten\Migrations\IDbal';

		} else {
			throw new Etten\Migrations\LogicException('Invalid dbal value');
		}
	}

	private function getDbalFactory($dbal)
	{
		if ($dbal instanceof DI\Statement) {
			return DI\Compiler::filterArguments([$dbal])[0];

		} elseif (is_string($dbal) && isset($this->dbals[$dbal])) {
			return $this->dbals[$dbal];

		} else {
			return NULL;
		}
	}

}
