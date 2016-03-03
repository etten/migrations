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

	/** @var IPrinter */
	private $printer;

	/** @var array (extension => IExtensionHandler) */
	private $extensionsHandlers = [];

	/** @var Group[] */
	private $groups = [];

	/** @var IDriver */
	private $driver;

	/** @var Finder */
	private $finder;

	/** @var OrderResolver */
	private $orderResolver;

	public function __construct(IDriver $driver, IPrinter $printer)
	{
		$this->driver = $driver;
		$this->printer = $printer;
		$this->finder = new Finder;
		$this->orderResolver = new OrderResolver;
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

		try {

			$this->driver->setupConnection();
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

		$this->driver->markMigrationAsReady($migration);
		$this->driver->commitTransaction();

		return $queriesCount;
	}

}
