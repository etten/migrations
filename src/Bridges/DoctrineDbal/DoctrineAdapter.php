<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations\Bridges\DoctrineDbal;

use DateTime;
use Doctrine;
use Etten\Migrations\IDbal;

class DoctrineAdapter implements IDbal
{

	/** @var Doctrine\DBAL\Connection */
	private $conn;

	public function __construct(Doctrine\DBAL\Connection $conn)
	{
		$this->conn = $conn;
	}

	public function query(string $sql)
	{
		return $this->conn->fetchAll($sql);
	}

	public function exec(string $sql)
	{
		return $this->conn->exec($sql);
	}

	public function escapeString(string $value)
	{
		return $this->conn->quote($value, Doctrine\DBAL\Types\Type::STRING);
	}

	public function escapeInt(int $value)
	{
		return $this->conn->quote($value, Doctrine\DBAL\Types\Type::INTEGER);
	}

	public function escapeBool(bool $value)
	{
		return $this->conn->quote($value, Doctrine\DBAL\Types\Type::BOOLEAN);
	}

	public function escapeDateTime(DateTime $value)
	{
		return $this->conn->quote($value, Doctrine\DBAL\Types\Type::DATETIME);
	}

	public function escapeIdentifier(string $value)
	{
		return $this->conn->quoteIdentifier($value);
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
