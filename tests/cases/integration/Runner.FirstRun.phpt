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

class FirstRunTest extends IntegrationTestCase
{

	public function testReset()
	{
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

		$migrations = $this->driver->getAllMigrations();
		Assert::count(5, $migrations);

		Assert::same('001.sql', $migrations[0]->filename);
		Assert::type('string', $migrations[0]->checksum);
		Assert::same(TRUE, $migrations[0]->completed);
		Assert::type('DateTime', $migrations[0]->executedAt);
		Assert::same('structures', $migrations[0]->group);

		Assert::same('002.sql', $migrations[1]->filename);
		Assert::type('string', $migrations[1]->checksum);
		Assert::same(TRUE, $migrations[1]->completed);
		Assert::type('DateTime', $migrations[1]->executedAt);
		Assert::same('structures', $migrations[1]->group);

		Assert::same('003.sql', $migrations[2]->filename);
		Assert::type('string', $migrations[2]->checksum);
		Assert::same(TRUE, $migrations[2]->completed);
		Assert::type('DateTime', $migrations[2]->executedAt);
		Assert::same('basic-data', $migrations[2]->group);
	}

	public function testContinue()
	{
		$this->runner->run(Runner::MODE_CONTINUE);
		Assert::same([
			'Etten Migrations',
			'CONTINUE',
			'5 migrations need to be executed.',
			'- structures/001.sql; 1 queries; XX ms',
			'- structures/002.sql; 1 queries; XX ms',
			'- basic-data/003.sql; 2 queries; XX ms',
			'- dummy-data/004.sql; 1 queries; XX ms',
			'- structures/005.sql; 1 queries; XX ms',
			'OK',
		], $this->printer->lines);

		$migrations = $this->driver->getAllMigrations();
		Assert::count(5, $migrations);

		Assert::same('001.sql', $migrations[0]->filename);
		Assert::type('string', $migrations[0]->checksum);
		Assert::same(TRUE, $migrations[0]->completed);
		Assert::type('DateTime', $migrations[0]->executedAt);
		Assert::same('structures', $migrations[0]->group);

		Assert::same('002.sql', $migrations[1]->filename);
		Assert::type('string', $migrations[1]->checksum);
		Assert::same(TRUE, $migrations[1]->completed);
		Assert::type('DateTime', $migrations[1]->executedAt);
		Assert::same('structures', $migrations[1]->group);

		Assert::same('003.sql', $migrations[2]->filename);
		Assert::type('string', $migrations[2]->checksum);
		Assert::same(TRUE, $migrations[2]->completed);
		Assert::type('DateTime', $migrations[2]->executedAt);
		Assert::same('basic-data', $migrations[2]->group);
	}

	public function testInit()
	{
		$options = Tester\Environment::loadData();
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

(new FirstRunTest)->run();
