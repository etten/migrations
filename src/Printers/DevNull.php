<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations\Printers;

use Etten\Migrations\Entities\File;
use Etten\Migrations\Exception;
use Etten\Migrations\IPrinter;

/**
 * /dev/null printer
 * @author Petr Procházka
 */
class DevNull implements IPrinter
{

	public function printIntro(string $mode)
	{
	}

	public function printToExecute(array $toExecute)
	{
	}

	public function printExecute(File $file, int $count, float $time)
	{
	}

	public function printDone()
	{
	}

	public function printError(Exception $e)
	{
	}

	public function printSource(string $code)
	{
	}

}
