<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Etten\Migrations\Engine;

use Etten\Migrations\Entities\File;
use Etten\Migrations\Entities\Group;
use Etten\Migrations\Entities\Migration;
use Etten\Migrations\Exception;
use Etten\Migrations\LogicException;

class OrderResolver
{

	/**
	 * @param Migration[] $migrations
	 * @param Group[] $groups
	 * @param File[] $files
	 * @param string $mode
	 * @return File[]
	 * @throws Exception
	 */
	public function resolve(array $migrations, array $groups, array $files, string $mode)
	{
		$groups = $this->getAssocGroups($groups);
		$this->validateGroups($groups);

		if ($mode === Runner::MODE_RESET) {
			return $this->sortFiles($files, $groups);
		} elseif ($mode !== Runner::MODE_CONTINUE) {
			throw new LogicException('Unsupported mode.');
		}

		$migrations = $this->getAssocMigrations($migrations);
		$files = $this->getAssocFiles($files);

		foreach ($migrations as $groupName => $mg) {
			if (!isset($groups[$groupName])) {
				throw new LogicException(sprintf(
					'Existing migrations depend on unknown group "%s".',
					$groupName
				));
			}

			$group = $groups[$groupName];
			foreach ($mg as $filename => $migration) {
				if (!$migration->completed) {
					throw new LogicException(sprintf(
						'Previously executed migration "%s/%s" did not succeed. Please fix this manually or reset the migrations.',
						$groupName,
						$filename
					));
				}

				if (isset($files[$groupName][$filename])) {
					$file = $files[$groupName][$filename];
					if ($migration->checksum !== $file->checksum) {
						throw new LogicException(sprintf(
							'Previously executed migration "%s/%s" has been changed.',
							$groupName,
							$filename
						));
					}
					unset($files[$groupName][$filename]);

				} elseif ($group->enabled) {
					throw new LogicException(sprintf(
						'Previously executed migration "%s/%s" is missing.',
						$groupName,
						$filename
					));
				}
			}
		}

		$files = $this->getFlatFiles($files);
		$files = $this->sortFiles($files, $groups);

		return $files;
	}

	/**
	 * @param  File[] $files
	 * @param  array $groups (name => Group)
	 * @return File[] sorted
	 */
	protected function sortFiles(array $files, array $groups)
	{
		usort($files, function (File $a, File $b) use ($groups) {
			$cmp = strcmp($a->name, $b->name);
			if ($cmp === 0 && $a !== $b) {
				$cmpA = $this->isGroupDependentOn($groups, $a->group, $b->group);
				$cmpB = $this->isGroupDependentOn($groups, $b->group, $a->group);
				if ($cmpA xor $cmpB) {
					$cmp = ($cmpA ? -1 : 1);

				} else {
					$names = [
						"{$a->group->name}/{$a->name}",
						"{$b->group->name}/{$b->name}",
					];
					sort($names);
					throw new LogicException(sprintf(
						'Unable to determine order for migrations "%s" and "%s".',
						$names[0],
						$names[1]
					));
				}
			}

			return $cmp;
		});

		return $files;
	}

	/**
	 * Returns TRUE if groupA depends on groupB.
	 *
	 * @param  array $groups (name => Group)
	 * @param  Group $groupA
	 * @param  Group $groupB
	 * @return bool
	 */
	protected function isGroupDependentOn(array $groups, Group $groupA, Group $groupB)
	{
		$visited = [];
		$queue = $groupB->dependencies;
		while (($node = array_shift($queue))) {
			if (isset($visited[$node])) {
				continue;
			}

			if ($groupA->name === $node) {
				return TRUE;
			}

			$visited[$node] = TRUE;
			foreach ($groups[$node]->dependencies as $dep) {
				$queue[] = $dep;
			}
		}
		return FALSE;
	}

	protected function getAssocMigrations(array $migrations)
	{
		$assoc = [];
		foreach ($migrations as $migration) {
			$assoc[$migration->group][$migration->filename] = $migration;
		}
		return $assoc;
	}

	protected function getAssocGroups(array $groups)
	{
		$assoc = [];
		foreach ($groups as $group) {
			$assoc[$group->name] = $group;
		}
		return $assoc;
	}

	protected function getAssocFiles(array $files)
	{
		$assoc = [];
		foreach ($files as $file) {
			$assoc[$file->group->name][$file->name] = $file;
		}
		return $assoc;
	}

	protected function getFlatFiles(array $files)
	{
		$flat = [];
		foreach ($files as $tmp) {
			foreach ($tmp as $file) {
				$flat[] = $file;
			}
		}
		return $flat;
	}

	/**
	 * @param  Group[] $groups
	 * @return void
	 * @throws Exception
	 */
	private function validateGroups(array $groups)
	{
		foreach ($groups as $group) {
			foreach ($group->dependencies as $dependency) {
				if (!isset($groups[$dependency])) {
					throw new LogicException(sprintf(
						'Group "%s" depends on unknown group "%s".',
						$group->name,
						$dependency
					));

				} elseif ($group->enabled && !$groups[$dependency]->enabled) {
					throw new LogicException(sprintf(
						'Group "%s" depends on disabled group "%s". Please enable group "%s" to continue.',
						$group->name,
						$dependency,
						$dependency
					));
				}
			}
		}
	}

}
