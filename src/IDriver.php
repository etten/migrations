<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations;

use Etten\Migrations\Entities\File;
use Etten\Migrations\Entities\Migration;

/**
 * @author Jan Skrasek
 */
interface IDriver
{

	/** @const shared lock identifier */
	const LOCK_NAME = 'Etten.Migrations';

	/**
	 * Setups the connection, such as encoding, default schema, etc.
	 */
	public function setup();

	/**
	 * Drops the database / schema. Should removes all db objects (tables, views, procedures, sequences, ...)
	 * @return mixed
	 */
	public function emptyDatabase();

	/**
	 * Loads and executes SQL queries from given file.
	 * @param  string $path
	 * @return int number of executed queries
	 */
	public function loadFile(string $path);

	/**
	 * Starts transaction.
	 */
	public function beginTransaction();

	/**
	 * Commit transaction.
	 */
	public function commitTransaction();

	/**
	 * Rollback transaction.
	 */
	public function rollbackTransaction();

	/**
	 * Locks database for running migrations.
	 */
	public function lock();

	/**
	 * Unlocks database.
	 */
	public function unlock();

	/**
	 * Creates migration table.
	 */
	public function createTable();

	/**
	 * Drop migration table.
	 */
	public function dropTable();

	/**
	 * Inserts migration info into migration table.
	 * @param  Migration $migration
	 */
	public function insertMigration(Migration $migration);

	/**
	 * Updated migration as executed.
	 * @param  Migration $migration
	 */
	public function markMigrationAsReady(Migration $migration);

	/**
	 * Returns all migrations stored in migration table sorted by time.
	 * @return Migration[]
	 */
	public function getAllMigrations();

	/**
	 * Returns source code for migration table initialization.
	 * @return string
	 */
	public function getInitTableSource();

	/**
	 * Returns source code for migration table data initialization.
	 * @param  File[] $files
	 * @return string
	 */
	public function getInitMigrationsSource(array $files);

}
