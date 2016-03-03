<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations\Extensions;

use Etten\Migrations\Bridges\NetteDatabase\NetteAdapter;
use Etten\Migrations\Drivers\MySqlDriver;
use Etten\Migrations\Drivers\PgSqlDriver;
use Nette;

/**
 * @deprecated
 */
class NetteDbSql extends SqlHandler
{

	public function __construct(Nette\Database\Context $context)
	{
		trigger_error(sprintf('Class %s is deprecated, use class SqlHandler instead.', __CLASS__), E_USER_DEPRECATED);
		$connection = $context->getConnection();
		$driver = $connection->getSupplementalDriver();
		$dbal = new NetteAdapter($connection);

		if ($driver instanceof Nette\Database\Drivers\PgSqlDriver) {
			parent::__construct(new PgSqlDriver($dbal, 'migrations'));

		} elseif ($driver instanceof Nette\Database\Drivers\MySqlDriver) {
			parent::__construct(new MySqlDriver($dbal, 'migrations'));

		} else {
			throw new \LogicException();
		}
	}

	public function getName()
	{
		return 'sql';
	}

}
