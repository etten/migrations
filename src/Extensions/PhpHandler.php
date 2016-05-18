<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations\Extensions;

use Etten\Migrations\Entities\File;
use Etten\Migrations\IExtensionHandler;
use Etten\Migrations\IOException;

/**
 * @author Petr Procházka
 * @author Jan Tvrdík
 */
class PhpHandler implements IExtensionHandler
{

	/** @var array name => value */
	private $params = [];

	/**
	 * @param array $params name => value
	 */
	public function __construct(array $params = [])
	{
		foreach ($params as $name => $value) {
			$this->addParameter($name, $value);
		}
	}

	/**
	 * @param  string $name
	 * @param  mixed $value
	 * @return self
	 */
	public function addParameter(string $name, $value)
	{
		$this->params[$name] = $value;
		return $this;
	}

	/**
	 * @return array (name => value)
	 */
	public function getParameters()
	{
		return $this->params;
	}

	public function execute(File $file)
	{
		extract($this->params, EXTR_SKIP);
		$count = @include $file->path;
		if ($count === FALSE) {
			throw new IOException("Cannot include file '{$file->path}'.");
		}

		return $count;
	}

}
