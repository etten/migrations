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

class ContinueCommand extends BaseCommand
{

	protected function configure()
	{
		$this->setName('migrations:continue');
		$this->setDescription('Updates database schema by running all new migrations');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->runMigrations(Runner::MODE_CONTINUE);
	}

}
