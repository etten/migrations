<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations;

use DateTime;

/**
 * @author Jan Tvrdík
 */
interface IDbal
{

	/**
	 * @param string $sql
	 * @return array list of rows represented by assoc. arrays
	 */
	public function query(string $sql);

	/**
	 * @param string $sql
	 * @return int number of affected rows
	 */
	public function exec(string $sql);

	/**
	 * @param string $value
	 * @return string escaped string wrapped in quotes
	 */
	public function escapeString(string $value);

	/**
	 * @param int $value
	 * @return string
	 */
	public function escapeInt(int $value);

	/**
	 * @param bool $value
	 * @return string
	 */
	public function escapeBool(bool $value);

	/**
	 * @param DateTime $value
	 * @return string
	 */
	public function escapeDateTime(DateTime $value);

	/**
	 * @param string $value
	 * @return string
	 */
	public function escapeIdentifier(string $value);

	/**
	 * @return void
	 */
	public function beginTransaction();

	/**
	 * @return void
	 */
	public function commit();

	/**
	 * @return void
	 */
	public function rollBack();

}
