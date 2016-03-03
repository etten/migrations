<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations\Printers;

use Etten\Migrations\Engine\Runner;
use Etten\Migrations\Entities\File;

class FullTextPrinter extends TextPrinter
{

	public function printIntro(string $mode)
	{
		$this->output('Etten Migrations');
		if ($mode === Runner::MODE_RESET) {
			$this->output('RESET: All tables, views and data has been destroyed!');
		} else {
			$this->output('CONTINUE');
		}
	}

	public function printExecute(File $file, int $count, float $time)
	{
		$this->output(
			'- ' . $file->group->name . '/' . $file->name . '; '
			. $count . ' queries; '
			. sprintf('%0.3f', $time) . ' ms'
		);
	}

}
