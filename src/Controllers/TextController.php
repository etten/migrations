<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Controllers;

use Nextras\Migrations\Printers\TextPrinter;
use Nextras\Migrations\Engine\Runner;
use Nextras\Migrations\IDriver;

class TextController extends BaseController
{

	/** @var IDriver */
	private $driver;

	public function __construct(IDriver $driver)
	{
		parent::__construct($driver);
		$this->driver = $driver;
	}

	public function run()
	{
		$this->processArguments();
		$this->driver->createTable();
		$this->runner->run(Runner::MODE_CONTINUE);
	}

	protected function createPrinter()
	{
		return new TextPrinter();
	}

	private function processArguments()
	{
		if (isset($_GET['groups']) && is_array($_GET['groups'])) {
			foreach ($_GET['groups'] as $group) {
				if (is_string($group)) {
					if (isset($this->groups[$group])) {
						$this->groups[$group]->enabled = TRUE;
					} else {
						$error = sprintf(
							"Unknown group '%s', the following groups are registered: '%s'",
							$group,
							implode('\', \'', array_keys($this->groups))
						);
						throw new \Exception($error);
					}
				} else {
					throw new \Exception('Malformed groups parameter.');
				}
			}
		} else {
			foreach ($this->groups as $group) {
				$group->enabled = TRUE;
			}
		}

		$this->registerGroups();
	}

}
