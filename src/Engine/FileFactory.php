<?php

/**
 * This file is part of etten/migrations.
 * Copyright © 2016 Jaroslav Hranička <hranicka@outlook.com>
 */

namespace Etten\Migrations\Engine;

use Etten\Migrations\Entities\File;
use Etten\Migrations\Entities\Group;
use Etten\Migrations\Exception;
use Etten\Migrations\IOException;
use Etten\Migrations\LogicException;

class FileFactory
{

	/** @var array */
	private $extensions;

	public function __construct(array $extensions)
	{
		$this->extensions = $extensions;
	}

	public function create(string $path, Group $group): File
	{
		$file = new File;
		$file->group = $group;
		$file->name = $this->getName($path);
		$file->path = $group->directory . '/' . $path;
		$file->extension = $this->getExtension($file, $this->extensions);
		$file->checksum = $this->getChecksum($file);

		return $file;
	}

	/**
	 * Returns logical name of migration file.
	 * @param  string $path relative path to group directory
	 * @return string
	 */
	protected function getName(string $path)
	{
		$parts = explode('/', $path);
		$dirName = implode('-', array_slice($parts, 0, -1));
		$fileName = implode('-', array_slice($parts, -1));
		$isPrefix = strncmp($fileName, $dirName, strlen($dirName)) === 0;
		return ($isPrefix ? $fileName : "$dirName-$fileName");
	}

	/**
	 * Returns file extension.
	 * @param File $file
	 * @param string[] $extensions
	 * @return string
	 * @throws Exception
	 */
	protected function getExtension(File $file, array $extensions)
	{
		$fileExt = NULL;

		foreach ($extensions as $extension) {
			if (substr($file->name, -strlen($extension)) === $extension) {
				if ($fileExt !== NULL) {
					throw new LogicException(sprintf(
						'Finder: Extension of "%s" is ambiguous, both "%s" and "%s" can be used.',
						$file->group->directory . '/' . $file->name,
						$fileExt,
						$extension
					));

				} else {
					$fileExt = $extension;
				}
			}
		}

		if ($fileExt === NULL) {
			throw new LogicException(sprintf(
				'Finder: No extension matched "%s". Supported extensions are %s.',
				$file->group->directory . '/' . $file->name,
				'"' . implode('", "', $extensions) . '"'
			));
		}

		return $fileExt;
	}

	/**
	 * @param File $file
	 * @return string
	 */
	protected function getChecksum(File $file)
	{
		$content = @file_get_contents($file->path);
		if ($content === FALSE) {
			throw new IOException("Unable to read '$file->path'.");
		}

		return md5(str_replace(["\r\n", "\r"], "\n", $content));
	}

}
