<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations\Bridges\NetteDatabase;

use DateTime;
use Etten\Migrations\IDbal;
use Nette;
use PDO;

class NetteAdapter implements IDbal
{

	/** @var Nette\Database\Connection */
	private $conn;

	public function __construct(Nette\Database\Connection $ndb)
	{
		$this->conn = $ndb;
	}

	public function query(string $sql)
	{
		return array_map(
			function ($row) {
				return (array)$row;
			},
			$this->conn->fetchAll($sql)
		);
	}

	public function exec(string $sql)
	{
		return $this->conn->query($sql)->getRowCount();
	}

	public function escapeString(string $value)
	{
		return $this->conn->quote($value, PDO::PARAM_STR);
	}

	public function escapeInt(int $value)
	{
		return $this->conn->quote($value, PDO::PARAM_INT);
	}

	public function escapeBool(bool $value)
	{
		return $this->conn->getSupplementalDriver()->formatBool($value);
	}

	public function escapeDateTime(DateTime $value)
	{
		return $this->conn->getSupplementalDriver()->formatDateTime($value);
	}

	public function escapeIdentifier(string $value)
	{
		return $this->conn->getSupplementalDriver()->delimite($value);
	}

	public function beginTransaction()
	{
		$this->conn->beginTransaction();
	}

	public function commit()
	{
		$this->conn->commit();
	}

	public function rollBack()
	{
		$this->conn->rollBack();
	}

}
