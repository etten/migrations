<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations\Extensions;

use Etten\Migrations\Entities\File;

class PhpHandlerWithEvents extends PhpHandler
{

	/** @var callable[] */
	public $onBeforeExecute = [];

	/** @var callable[] */
	public $onAfterExecute = [];

	public function execute(File $sql)
	{
		$this->runCallbacks($this->onBeforeExecute);
		$return = parent::execute($sql);
		$this->runCallbacks($this->onAfterExecute);

		return $return;
	}

	private function runCallbacks(array $callbacks)
	{
		foreach ($callbacks as $callback) {
			call_user_func($callback, $this);
		}
	}

}
