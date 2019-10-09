<?php

namespace Ligacom\Feed\Reference\Storage;

use Bitrix\Main;
use Ligacom\Feed\Config;

class Controller
{
	public static function createTable($classList = null)
	{
		$className = Table::getClassName();

		if ($classList === null)
		{
			$classList = static::getClassList($className);
		}

		/** @var Table $className */
		foreach ($classList as $className)
		{
			$entity = $className::getEntity();
			$connection = $entity->getConnection();
			$tableName = $entity->getDBTableName();

			if ($connection->isTableExists($tableName))
			{
				$className::migrate($connection);
			}
			else
			{
				$entity->createDbTable();
				$className::createIndexes($connection);
			}
		}
	}

	public static function dropTable()
	{
		$className = Table::getClassName();
		$classList = static::getClassList($className);

		/** @var Table $className */
		foreach ($classList as $className)
		{
			$entity = $className::getEntity();
			static::internalDropTable($entity);
		}
	}

    /**
     * @param $baseClassName
     * @return array
     */
	protected static function getClassList($baseClassName)
	{
		$baseDir = Config::getModulePath();
		$baseNamespace = Config::getNamespace();
		$directory = new \RecursiveDirectoryIterator($baseDir);
		$iterator = new \RecursiveIteratorIterator($directory);
		$result = [];

		/** @var \DirectoryIterator $entry */
		foreach ($iterator as $entry)
		{
			if (
				$entry->isFile()
				&& $entry->getExtension() === 'php'
			)
			{
				$relativePath = str_replace($baseDir, '', $entry->getPath());
				$namespace = $baseNamespace . str_replace('/', '\\', $relativePath) . '\\';
				$className = $entry->getBasename('.php');

				if ($className !== 'table')
				{
					$className .= 'Table';
				}

				$fullClassName = $namespace . $className;

				if (
					class_exists($fullClassName)
					&& is_subclass_of($fullClassName, $baseClassName)
				)
				{
					$result[] = $fullClassName;
				}
			}
		}

		return $result;
	}

	protected static function internalDropTable(Main\Entity\Base $entity)
	{
		$connection = $entity->getConnection();
		$tableName = $entity->getDBTableName();

		if ($connection->isTableExists($tableName))
		{
			$connection->dropTable($tableName);
		}
	}
}
