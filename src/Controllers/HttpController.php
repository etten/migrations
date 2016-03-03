<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations\Controllers;

use Etten\Migrations\Engine;
use Etten\Migrations\Printers;

class HttpController extends BaseController
{

	/** @var string */
	private $action;

	/** @var string */
	private $error;

	public function run()
	{
		$this->processArguments();
		$this->executeAction();
	}

	private function processArguments()
	{
		if (isset($_GET['action'])) {
			if ($_GET['action'] === 'run' || $_GET['action'] === 'css') {
				$this->action = $_GET['action'];
			} else {
				$this->action = 'error';
			}
		} else {
			$this->action = 'index';
		}

		if ($this->action === 'run') {
			if (isset($_GET['groups']) && is_array($_GET['groups'])) {
				foreach ($_GET['groups'] as $group) {
					if (is_string($group)) {
						if (isset($this->groups[$group])) {
							$this->groups[$group]->enabled = TRUE;
						} else {
							$error = sprintf(
								"Unknown group '%s', the following groups are registered: '%s'",
								$group, implode('\', \'', array_keys($this->groups))
							);
							goto error;
						}
					} else {
						$error = 'Malformed groups parameter.';
						goto error;
					}
				}
			} else {
				foreach ($this->groups as $group) {
					$group->enabled = TRUE;
				}
			}

			if (!isset($_GET['mode'])) {
				$error = 'Missing mode parameter.';
				goto error;
			}

			switch ($_GET['mode']) {
				case '0':
					$this->mode = Engine\Runner::MODE_CONTINUE;
					break;
				case '1':
					$this->mode = Engine\Runner::MODE_RESET;
					break;
				case '2':
					$this->mode = Engine\Runner::MODE_INIT;
					break;
				default:
					$error = 'Unknown mode.';
					goto error;
			}
		}

		return;

		error:
		$this->action = 'error';
		$this->error = $error;
	}

	private function executeAction()
	{
		$method = 'action' . ucfirst($this->action);
		$this->$method();
	}

	private function actionIndex()
	{
		$this->printHeader();

		$modes = [
			0 => '<h2 class="continue">Continue</h2>',
			1 => '<h2 class="reset">Reset <small>All tables, views and data will be destroyed!</small></h2>',
			2 => '<h2 class="init">Init SQL</h2>',
		];

		echo "<h1>Migrations</h1>\n";
		foreach ($modes as $mode => $heading) {
			echo "<div class='mode mode-{$mode}'>\n";

			$query = htmlspecialchars(http_build_query(['action' => 'run', 'mode' => $mode]));
			$alert = $mode === 1 ? ' onclick="return confirm(\'Are you really sure?\')"' : '';
			echo "<a href=\"?$query\"{$alert}>$heading</a>\n";

			echo "</div>\n\n";
		}
	}

	private function actionRun()
	{
		$groups = $this->registerGroups();
		$groups = implode(' + ', $groups);

		$this->printHeader();
		echo "<h1>Migrations – $groups</h1>\n";
		echo "<div class=\"output\">";
		$this->runner->run($this->mode);
		echo "</div>\n";
	}

	private function actionCss()
	{
		header('Content-Type: text/css', TRUE);
		readfile(__DIR__ . '/templates/main.css');
	}

	private function actionError()
	{
		$this->printHeader();
		echo "<h1>Migrations – error</h1>\n";
		echo "<div class=\"error-message\">" . nl2br(htmlspecialchars($this->error), FALSE) . "</div>\n";
	}

	private function printHeader()
	{
		readfile(__DIR__ . '/templates/header.phtml');
	}

	protected function createPrinter()
	{
		return new Printers\HtmlDump();
	}

}
