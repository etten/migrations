<?php

/**
 * This file is part of etten/migrations.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Migrations\Engine;

use Etten\Migrations\IDriver;

class Forced
{

	/** @var IDriver */
	private $driver;

	/** @var array */
	private $files = [];

	public function __construct(IDriver $driver, array $files)
	{
		$this->driver = $driver;
		$this->files = $files;
	}

	public function execute()
	{
		foreach ($this->files as $file) {
			$this->driver->loadFileAndSuppressErrors($file);
		}
	}

}
