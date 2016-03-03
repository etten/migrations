<?php

namespace Etten\Migrations;

use DibiConnection;
use Doctrine;
use Etten;
use Etten\Migrations\Bridges\Dibi\DibiAdapter;
use Etten\Migrations\Bridges\DoctrineDbal\DoctrineAdapter;
use Etten\Migrations\Bridges\NetteDatabase\NetteAdapter;
use Etten\Migrations\Engine\Runner;
use Etten\Migrations\Entities\Group;
use Nette;
use Tester\Environment;
use Tester\TestCase;

abstract class IntegrationTestCase extends TestCase
{

	/** @var IDbal */
	protected $dbal;

	/** @var IDriver */
	protected $driver;

	/** @var IPrinter|TestPrinter */
	protected $printer;

	/** @var Runner */
	protected $runner;

	/** @var string */
	protected $dbName;

	/** @var string */
	protected $fixtureDir;

	protected function setUp()
	{
		parent::setUp();

		$options = Environment::loadData();
		$driversConfig = parse_ini_file(__DIR__ . '/../drivers.ini', TRUE);
		$dbalOptions = $driversConfig[$options['driver']] + $options;

		$this->fixtureDir = __DIR__ . '/../fixtures/' . $options['driver'];
		$this->dbName = $dbalOptions['database'] . '_' . bin2hex(openssl_random_pseudo_bytes(4));
		$this->dbal = $this->createDbal($dbalOptions);

		$initDb = require $this->fixtureDir . '/init.php';
		$initDb = \Closure::bind($initDb, $this);
		$initDb();

		$this->driver = $this->createDriver($options['driver'], $this->dbal);
		$this->printer = $this->createPrinter();
		$this->runner = new Runner($this->driver, $this->printer);

		foreach ($this->getGroups($this->fixtureDir) as $group) {
			$this->runner->addGroup($group);
		}

		foreach ($this->getExtensionHandlers() as $ext => $handler) {
			$this->runner->addExtensionHandler($ext, $handler);
		}
	}

	protected function tearDown()
	{
		parent::tearDown();
		$cleanupDb = require $this->fixtureDir . '/cleanup.php';
		$cleanupDb = \Closure::bind($cleanupDb, $this);
		$cleanupDb();
	}

	protected function getGroups($dir)
	{
		$structures = new Group();
		$structures->enabled = TRUE;
		$structures->name = 'structures';
		$structures->directory = $dir . '/structures';
		$structures->dependencies = [];

		$basicData = new Group();
		$basicData->enabled = TRUE;
		$basicData->name = 'basic-data';
		$basicData->directory = $dir . '/basic-data';
		$basicData->dependencies = ['structures'];

		$dummyData = new Group();
		$dummyData->enabled = TRUE;
		$dummyData->name = 'dummy-data';
		$dummyData->directory = $dir . '/dummy-data';
		$dummyData->dependencies = ['structures', 'basic-data'];

		return [$structures, $basicData, $dummyData];
	}

	/**
	 * @return array (extension => IExtensionHandler)
	 */
	protected function getExtensionHandlers()
	{
		return [
			'sql' => new Etten\Migrations\Extensions\SqlHandler($this->driver, $this->dbal),
		];
	}

	/**
	 * @param  array $options
	 * @return IDbal
	 * @throws \Exception
	 */
	protected function createDbal($options)
	{
		switch ($options['dbal']) {
			case 'dibi':
				$drivers = [
					'mysql' => 'mysqli',
					'pgsql' => 'postgre',
				];
				return new DibiAdapter(new DibiConnection([
					'host' => $options['host'],
					'username' => $options['username'],
					'password' => $options['password'],
					'database' => $options['database'],
					'driver' => $drivers[$options['driver']],
				]));

			case 'doctrine':
				$drivers = [
					'mysql' => 'mysqli',
					'pgsql' => 'pdo_pgsql',
				];
				return new DoctrineAdapter(Doctrine\DBAL\DriverManager::getConnection([
					'host' => $options['host'],
					'user' => $options['username'],
					'password' => $options['password'],
					'database' => $options['database'],
					'driver' => $drivers[$options['driver']],
				]));

			case 'nette':
				return new NetteAdapter(new Nette\Database\Connection(
					"$options[driver]:host=$options[host];dbname=$options[database]",
					$options['username'],
					$options['password']
				));

			default:
				throw new \Exception("Unknown DBAL '$options[dbal]'.");
		}
	}

	/**
	 * @param  array $name
	 * @param  IDbal $dbal
	 * @return IDriver
	 */
	protected function createDriver($name, IDbal $dbal)
	{
		switch ($name) {
			case 'mysql':
				return new Etten\Migrations\Drivers\MySqlDriver($dbal, 'm');

			case 'pgsql':
				return new Etten\Migrations\Drivers\PgSqlDriver($dbal, 'm', $this->dbName);
		}
	}

	/**
	 * @return IPrinter
	 */
	protected function createPrinter()
	{
		return new TestPrinter();
	}

}
