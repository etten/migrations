<?php

/**
 * @testCase
 * @dataProvider ../../dbals.ini
 */

namespace Etten\Migrations;

use Etten\Migrations\Engine\Runner;
use Mockery;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

class EmptyRunTest extends IntegrationTestCase
{

	public function testReset()
	{
		$this->runner->run(Runner::MODE_RESET);
		Assert::same([
			'Etten Migrations',
			'RESET',
			'No migration needs to be executed.',
			'OK',
		], $this->printer->lines);

		Assert::count(0, $this->driver->getAllMigrations());
	}

	protected function getGroups($dir)
	{
		return [];
	}

}

(new EmptyRunTest)->run();
