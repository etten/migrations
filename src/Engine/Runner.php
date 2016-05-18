<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations\Engine;

use DateTime;
use Etten\Migrations\Entities\File;
use Etten\Migrations\Entities\Group;
use Etten\Migrations\Entities\Migration;
use Etten\Migrations\Exception;
use Etten\Migrations\ExecutionException;
use Etten\Migrations\IDriver;
use Etten\Migrations\IExtensionHandler;
use Etten\Migrations\IPrinter;
use Etten\Migrations\LogicException;

class Runner
{

	/** @const modes */
	const MODE_CONTINUE = 'continue';
	const MODE_RESET = 'reset';
	const MODE_INIT = 'init';

	/** @var callable[] */
	private $onStart = [];

	/** @var callable[] */
	private $onBeforeMigration = [];

	/** @var callable[] */
	private $onAfterMigration = [];

	/** @var callable[] */
	private $onFinish = [];

	/** @var IDriver */
	private $driver;

	/** @var IPrinter */
	private $printer;

	/** @var array (extension => IExtensionHandler) */
	private $extensionsHandlers = [];

	/** @var Group[] */
	private $groups = [];

	/** @var Finder */
	private $finder;

	/** @var OrderResolver */
	private $orderResolver;

	public function __construct(IDriver $driver)
	{
		$this->driver = $driver;
		$this->finder = new Finder;
		$this->orderResolver = new OrderResolver;
	}

	public function addOnStart(callable $callback)
	{
		$this->onStart[] = $callback;
		return $this;
	}

	public function addOnBeforeMigration(callable $callback)
	{
		$this->onBeforeMigration[] = $callback;
		return $this;
	}

	public function addOnAfterMigration(callable $callback)
	{
		$this->onAfterMigration[] = $callback;
		return $this;
	}

	public function addOnFinish(callable $callback)
	{
		$this->onFinish[] = $callback;
		return $this;
	}

	public function setPrinter(IPrinter $printer)
	{
		$this->printer = $printer;
		return $this;
	}

	public function addGroup(Group $group)
	{
		$this->groups[] = $group;
		return $this;
	}

	/**
	 * @param  string $extension
	 * @param  IExtensionHandler $handler
	 * @return self
	 */
	public function addExtensionHandler(string $extension, IExtensionHandler $handler)
	{
		if (isset($this->extensionsHandlers[$extension])) {
			throw new LogicException("Extension '$extension' has already been defined.");
		}

		$this->extensionsHandlers[$extension] = $handler;
		return $this;
	}

	/**
	 * @param  string $mode self::MODE_CONTINUE|self::MODE_RESET|self::MODE_INIT
	 * @return void
	 */
	public function run(string $mode = self::MODE_CONTINUE)
	{
		if ($mode === self::MODE_INIT) {
			$this->printer->printSource($this->driver->getInitTableSource() . "\n");
			$files = $this->finder->find($this->groups, array_keys($this->extensionsHandlers));
			$files = $this->orderResolver->resolve([], $this->groups, $files, self::MODE_RESET);
			$this->printer->printSource($this->driver->getInitMigrationsSource($files));
			return;
		}

		$this->runHandlers($this->onStart, [$this]);

		try {
			$this->driver->setup();
			$this->driver->lock();

			$this->printer->printIntro($mode);
			if ($mode === self::MODE_RESET) {
				$this->driver->emptyDatabase();
			}

			$this->driver->createTable();
			$migrations = $this->driver->getAllMigrations();
			$files = $this->finder->find($this->groups, array_keys($this->extensionsHandlers));
			$toExecute = $this->orderResolver->resolve($migrations, $this->groups, $files, $mode);
			$this->printer->printToExecute($toExecute);

			foreach ($toExecute as $file) {
				$time = microtime(TRUE);
				$queriesCount = $this->execute($file);
				$this->printer->printExecute($file, $queriesCount, microtime(TRUE) - $time);
			}

			$this->driver->unlock();
			$this->printer->printDone();

		} catch (Exception $e) {
			$this->driver->unlock();
			$this->printer->printError($e);
		}

		$this->runHandlers($this->onFinish, [$this]);
	}

	/**
	 * @param  string $name
	 * @return IExtensionHandler
	 */
	public function getExtension(string $name)
	{
		if (!isset($this->extensionsHandlers[$name])) {
			throw new LogicException("Extension '$name' not found.");
		}
		return $this->extensionsHandlers[$name];
	}

	/**
	 * @param  File $file
	 * @return int  number of executed queries
	 */
	protected function execute(File $file)
	{
		$this->driver->beginTransaction();

		$this->runHandlers($this->onBeforeMigration, [$file]);

		$migration = new Migration;
		$migration->group = $file->group->name;
		$migration->filename = $file->name;
		$migration->checksum = $file->checksum;
		$migration->executedAt = new DateTime('now');

		$this->driver->insertMigration($migration);

		try {
			$queriesCount = $this->getExtension($file->extension)->execute($file);
		} catch (\Exception $e) {
			$this->driver->rollbackTransaction();
			throw new ExecutionException(sprintf('Executing migration "%s" has failed.', $file->path), NULL, $e);
		}

		$this->runHandlers($this->onAfterMigration, [$file]);

		$this->driver->markMigrationAsReady($migration);
		$this->driver->commitTransaction();

		return $queriesCount;
	}

	private function runHandlers(array $handlers, array $params = [])
	{
		foreach ($handlers as $callback) {
			call_user_func_array($callback, $params);
		}
	}

}
