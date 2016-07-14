<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations\Controllers;

use Etten\Migrations\Engine;
use Etten\Migrations\Entities\Group;
use Etten\Migrations\IExtensionHandler;

abstract class BaseController
{

	/** @var Engine\Runner */
	protected $runner;

	/** @var string */
	protected $mode;

	/** @var array (name => Group) */
	protected $groups;

	public function __construct(Engine\Runner $runner)
	{
		$this->runner = $runner;
		$this->runner->setPrinter($this->createPrinter());

		$this->mode = Engine\Runner::MODE_CONTINUE;
		$this->groups = [];
	}

	abstract public function run();

	public function addGroup($name, $dir, array $dependencies = [])
	{
		$group = new Group;
		$group->name = $name;
		$group->directory = $dir;
		$group->dependencies = $dependencies;
		$group->enabled = FALSE;

		$this->groups[$name] = $group;
		return $this;
	}

	public function addExtension($extension, IExtensionHandler $handler)
	{
		$this->runner->addExtensionHandler($extension, $handler);
		return $this;
	}

	protected function registerGroups()
	{
		$enabled = [];
		foreach ($this->groups as $group) {
			$this->runner->addGroup($group);
			if ($group->enabled) {
				$enabled[] = $group->name;
			}
		}
		return $enabled;
	}

	protected function setupPhp()
	{
		@set_time_limit(0);
		@ini_set('memory_limit', '1G');
	}

	abstract protected function createPrinter();

}
