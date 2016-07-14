<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations\Bridges\SymfonyConsole;

use Etten\Migrations\Engine\Runner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends BaseCommand
{

	protected function configure()
	{
		$this->setName('migrations:init');
		$this->setDescription('Prints initialization SQL for all present migrations.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->runMigrations(Runner::MODE_INIT);
	}

}
