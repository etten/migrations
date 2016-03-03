<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations\Bridges\Dibi;

use DateTime;
use dibi;
use DibiConnection;
use Etten\Migrations\IDbal;

class DibiAdapter implements IDbal
{

	/** @var DibiConnection */
	private $conn;

	public function __construct(DibiConnection $dibi)
	{
		$this->conn = $dibi;
	}

	public function query(string $sql)
	{
		$result = $this->conn->nativeQuery($sql);
		$result->setRowClass(NULL);
		return $result->fetchAll();
	}

	public function exec(string $sql)
	{
		return $this->conn->nativeQuery($sql);
	}

	public function escapeString(string $value)
	{
		return $this->conn->getDriver()->escape($value, dibi::TEXT);
	}

	public function escapeInt(int $value)
	{
		return (int)$value;
	}

	public function escapeBool(bool $value)
	{
		return $this->conn->getDriver()->escape($value, dibi::BOOL);
	}

	public function escapeDateTime(DateTime $value)
	{
		return $this->conn->getDriver()->escape($value, dibi::DATETIME);
	}

	public function escapeIdentifier(string $value)
	{
		return $this->conn->getDriver()->escape($value, dibi::IDENTIFIER);
	}

}
