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

class SecondRunTest extends IntegrationTestCase
{

	public function testReset()
	{
		$this->driver->loadFile($this->fixtureDir . '/3ok.sql');
		Assert::count(3, $this->driver->getAllMigrations());

		$this->runner->run(Runner::MODE_RESET);
		Assert::same([
			'Etten Migrations',
			'RESET',
			'5 migrations need to be executed.',
			'- structures/001.sql; 1 queries; XX ms',
			'- structures/002.sql; 1 queries; XX ms',
			'- basic-data/003.sql; 2 queries; XX ms',
			'- dummy-data/004.sql; 1 queries; XX ms',
			'- structures/005.sql; 1 queries; XX ms',
			'OK',
		], $this->printer->lines);

		Assert::count(5, $this->driver->getAllMigrations());
	}

	public function testContinueOk()
	{
		$this->driver->loadFile($this->fixtureDir . '/3ok.sql');
		Assert::count(3, $this->driver->getAllMigrations());

		$this->runner->run(Runner::MODE_CONTINUE);
		Assert::same([
			'Etten Migrations',
			'CONTINUE',
			'2 migrations need to be executed.',
			'- dummy-data/004.sql; 1 queries; XX ms',
			'- structures/005.sql; 1 queries; XX ms',
			'OK',
		], $this->printer->lines);

		Assert::count(5, $this->driver->getAllMigrations());
	}

	public function testContinueError()
	{
		$this->driver->loadFile($this->fixtureDir . '/2ok, 1ko.sql');
		Assert::count(3, $this->driver->getAllMigrations());

		Assert::throws(function () {
			$this->runner->run(Runner::MODE_CONTINUE);
		}, 'Etten\Migrations\LogicException');

		Assert::same([
			'Etten Migrations',
			'CONTINUE',
			'ERROR: Previously executed migration "basic-data/003.sql" did not succeed. Please fix this manually or reset the migrations.',
		], $this->printer->lines);

		Assert::count(3, $this->driver->getAllMigrations());
	}

	public function testInit()
	{
		$options = Tester\Environment::loadData();
		$this->driver->loadFile($this->fixtureDir . '/3ok.sql');
		$this->runner->run(Runner::MODE_INIT);

		$files = [
			__DIR__ . "/Runner.FirstRun.init.$options[driver].$options[dbal].txt",
			__DIR__ . "/Runner.FirstRun.init.$options[driver].txt",
		];

		foreach ($files as $file) {
			if (is_file($file)) {
				Assert::matchFile($file, $this->printer->out);
				break;
			}
		}
	}

}

(new SecondRunTest)->run();
