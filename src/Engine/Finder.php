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
use Etten\Migrations\Exception;
use Etten\Migrations\IOException;

class Finder
{

	/** @var FileFactory */
	private $fileFactory;

	public function __construct(FileFactory $fileFactory)
	{
		$this->fileFactory = $fileFactory;
	}

	/**
	 * Finds files.
	 *
	 * @param Group[] $groups
	 * @return File[]
	 * @throws Exception
	 */
	public function find(array $groups)
	{
		$files = [];
		foreach ($groups as $group) {
			if (!$group->enabled) {
				continue;
			}

			foreach ($this->getFilesRecursive($group->directory) as $path) {
				$files[] = $this->fileFactory->create($path, $group);
			}
		}
		return $files;
	}

	/**
	 * @param string $dir
	 * @return string[]
	 * @throws IOException
	 */
	protected function getFilesRecursive(string $dir)
	{
		$items = $this->getItems($dir);
		foreach ($items as $i => $item) {
			// skip '.', '..' and hidden files
			if ($item[0] === '.') {
				unset($items[$i]);

				// year or month
			} elseif (ctype_digit($item) /*&& is_dir($item)*/) {
				unset($items[$i]);
				foreach ($this->getFilesRecursive("$dir/$item") as $subItem) {
					$items[] = "$item/$subItem";
				}
			}
		}

		return array_values($items);
	}

	/**
	 * @param string $dir
	 * @return array
	 * @throws IOException
	 */
	protected function getItems(string $dir)
	{
		$items = @scandir($dir); // directory may not exist
		if ($items === FALSE) {
			throw new IOException(sprintf('Finder: Directory "%s" does not exist.', $dir));
		}
		return $items;
	}

}
