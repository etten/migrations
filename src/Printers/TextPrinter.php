<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Printers;

use Nextras\Migrations\Entities\File;
use Nextras\Migrations\Exception;
use Nextras\Migrations\IPrinter;

class TextPrinter implements IPrinter
{

	public function printIntro($mode)
	{
		// Suppress output
	}

	public function printToExecute(array $toExecute)
	{
		if ($toExecute) {
			$count = count($toExecute);
			$this->output($count . ' migration' . ($count > 1 ? 's' : '') . ' need' . ($count > 1 ? '' : 's') . ' to be executed.');
		} else {
			$this->output('No migration needs to be executed.');
		}
	}

	public function printExecute(File $file, $count, $time)
	{
		// Suppress output
	}

	public function printDone()
	{
		$this->output('OK');
	}

	public function printError(Exception $e)
	{
		$this->output('ERROR: ' . $e->getMessage());
		throw $e;
	}

	public function printSource($code)
	{
		$this->output($code);
	}

	protected function output($s)
	{
		echo "$s\n";
	}

}
