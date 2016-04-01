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

	/** @var array */
	private $groups;

	/** @var array */
	private $extensionHandlers;

	/**
	 * @param IDriver $driver
	 * @param array $groups
	 * @param array $extensionHandlers
	 */
	public function __construct(IDriver $driver, array $groups, $extensionHandlers = [])
	{
		parent::__construct();
		$this->driver = $driver;
		$this->groups = $groups;
		$this->extensionHandlers = $extensionHandlers;
	}

	/**
	 * @param string $mode Runner::MODE_*
	 * @return void
	 */
	protected function runMigrations(string $mode)
	{
		$printer = $this->getPrinter();
		$runner = new Runner($this->driver, $printer);

		foreach ($this->getGroups() as $group) {
			$runner->addGroup($group);
		}

		foreach ($this->getExtensionHandlers() as $ext => $handler) {
			$runner->addExtensionHandler($ext, $handler);
		}

		$runner->run($mode);
	}

	/**
	 * @return Group[]
	 */
	protected function getGroups()
	{
		$groups = [];

		foreach ($this->groups as $name => $config) {
			$group = new Group();
			$group->enabled = $config['enabled'] ?? TRUE;
			$group->name = $name;
			$group->directory = $config['directory'];
			$group->dependencies = $config['dependencies'] ?? [];

			$groups[] = $group;
		}

		return $groups;
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
