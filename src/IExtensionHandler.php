<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations;

use Etten\Migrations\Entities\File;

/**
 * @author Petr Procházka
 * @author Jan Tvrdík
 */
interface IExtensionHandler
{

	/**
	 * @param  File $file
	 * @return int number of queries
	 */
	public function execute(File $file);

}
