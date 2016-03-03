<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations\Bridges\SymfonyConsole;

use Etten\Migrations\Engine\Runner;
use Etten\Migrations\Entities\Group;
use Etten\Migrations\Extensions;
use Etten\Migrations\IDriver;
use Etten\Migrations\IPrinter;
use Etten\Migrations\Printers\Console;
use Symfony\Component\Console\Command\Command;

abstract class BaseCommand extends Command
{

	/** @var IDriver */
	private $driver;

	/** @var string */
	private $dir;

	/** @var array */
	private $extensionHandlers;

	/**
	 * @param  IDriver $driver
	 * @param  string $dir
	 * @param  array $extensionHandlers
	 */
	public function __construct(IDriver $driver, string $dir, $extensionHandlers = [])
	{
		parent::__construct();
		$this->driver = $driver;
		$this->dir = $dir;
		$this->extensionHandlers = $extensionHandlers;
	}

	/**
	 * @param  string $mode Runner::MODE_*
	 * @param  bool $withDummy include dummy data?
	 * @return void
	 */
	protected function runMigrations(string $mode, bool $withDummy)
	{
		$printer = $this->getPrinter();
		$runner = new Runner($this->driver, $printer);

		foreach ($this->getGroups($withDummy) as $group) {
			$runner->addGroup($group);
		}

		foreach ($this->getExtensionHandlers() as $ext => $handler) {
			$runner->addExtensionHandler($ext, $handler);
		}

		$runner->run($mode);
	}

	/**
	 * @param  bool $withDummy
	 * @return Group[]
	 */
	protected function getGroups($withDummy)
	{
		$structures = new Group();
		$structures->enabled = TRUE;
		$structures->name = 'structures';
		$structures->directory = $this->dir . '/structures';
		$structures->dependencies = [];

		$basicData = new Group();
		$basicData->enabled = TRUE;
		$basicData->name = 'basic-data';
		$basicData->directory = $this->dir . '/basic-data';
		$basicData->dependencies = ['structures'];

		$dummyData = new Group();
		$dummyData->enabled = $withDummy;
		$dummyData->name = 'dummy-data';
		$dummyData->directory = $this->dir . '/dummy-data';
		$dummyData->dependencies = ['structures', 'basic-data'];

		return [$structures, $basicData, $dummyData];
	}

	/**
	 * @return array (extension => IExtensionHandler)
	 */
	protected function getExtensionHandlers()
	{
		return $this->extensionHandlers;
	}

	/**
	 * @return IPrinter
	 */
	protected function getPrinter()
	{
		return new Console();
	}

}
