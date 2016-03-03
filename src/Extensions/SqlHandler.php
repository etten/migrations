<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations\Extensions;

use Etten\Migrations\Entities\File;
use Etten\Migrations\IDriver;
use Etten\Migrations\IExtensionHandler;
use Etten\Migrations\LogicException;

/**
 * @author Jan Tvrdík
 */
class SqlHandler implements IExtensionHandler
{

	/** @var IDriver */
	private $driver;

	/**
	 * @param IDriver $driver
	 */
	public function __construct(IDriver $driver)
	{
		$this->driver = $driver;
	}

	public function execute(File $sql)
	{
		$count = $this->driver->loadFile($sql->path);
		if ($count === 0) {
			throw new LogicException("{$sql->path} is empty");
		}
		return $count;
	}

}
