<?php

/**
 * This file is part of MetaModels/attribute_alias.
 *
 * (c) 2012-2020 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_alias
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2020 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_alias/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

declare(strict_types=1);

namespace MetaModels\AttributeCombinedValuesBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Types\TextType;

/**
 * This migration change the column from varchar(255) to text.
 *
 * This solves https://github.com/MetaModels/attribute_combinedvalues/issues/7.
 */
class ChangeColumnTypeMigration extends AbstractMigration
{
    /**
     * The database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * Create a new instance.
     *
     * @param Connection $connection The database connection.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Return the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Change the column from varchar(255) to text in MetaModels "combinedvalues" attributes.';
    }

    /**
     * Must only run if:
     * - the MM tables are present AND
     * - there are some columns defined AND
     * - these columns do not allow null values yet.
     *
     * @return bool
     */
    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_metamodel', 'tl_metamodel_attribute'])) {
            return false;
        }

        $langColumns = $this->fetchNotTypeTextColumns();
        if (empty($langColumns)) {
            return false;
        }

        return true;
    }

    /**
     * Collect the columns to be updated and update them.
     *
     * @return MigrationResult
     */
    public function run(): MigrationResult
    {
        $langColumns = $this->fetchNotTypeTextColumns();
        $message     = [];
        foreach ($langColumns as $tableName => $tableColumnNames) {
            foreach ($tableColumnNames as $tableColumnName) {
                $this->fixColumn($tableName, $tableColumnName);
                $message[] = $tableName . '.' . $tableColumnName;
            }
        }

        return new MigrationResult(true, 'Adjusted column(s): ' . implode(', ', $message));
    }

    /**
     * Fetch all columns that are not text type.
     *
     * @return array
     */
    private function fetchNotTypeTextColumns(): array
    {
        $langColumns = $this->fetchColumnNames();
        if (empty($langColumns)) {
            return [];
        }
        $schemaManager = $this->connection->getSchemaManager();

        $result = [];
        foreach ($langColumns as $tableName => $tableColumnNames) {
            $columns = $schemaManager->listTableColumns($tableName);
            foreach ($tableColumnNames as $tableColumnName) {
                $column = ($columns[$tableColumnName] ?? null);
                if (null === $column) {
                    continue;
                }
                if (($column->getType() instanceof TextType)) {
                    continue;
                }
                $result[$tableName][$column->getName()] = $column->getName();
            }
        }

        return $result;
    }

    /**
     * Obtain the names of table columns.
     *
     * @return array
     */
    private function fetchColumnNames(): array
    {
        $langColumns = $this
            ->connection
            ->createQueryBuilder()
            ->select('metamodel.tableName AS metamodel', 'attribute.colName AS attribute')
            ->from('tl_metamodel_attribute', 'attribute')
            ->leftJoin('attribute', 'tl_metamodel', 'metamodel', 'attribute.pid = metamodel.id')
            ->where('attribute.type=:type')
            ->setParameter('type', 'combinedvalues')
            ->execute()
            ->fetchAll(FetchMode::ASSOCIATIVE);

        $result = [];
        foreach ($langColumns as $langColumn) {
            if (!isset($result[$langColumn['metamodel']])) {
                $result[$langColumn['metamodel']] = [];
            }
            $result[$langColumn['metamodel']][] = $langColumn['attribute'];
        }

        return $result;
    }

    /**
     * Fix a table column.
     *
     * @param string $tableName  The name of the table.
     * @param string $columnName The name of the column.
     *
     * @return void
     */
    private function fixColumn(string $tableName, string $columnName): void
    {
        $this->connection->query(
            \sprintf('ALTER TABLE %1$s CHANGE %2$s %2$s %3$s', $tableName, $columnName, 'text')
        );
    }
}
