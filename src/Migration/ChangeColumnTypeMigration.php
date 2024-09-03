<?php

/**
 * This file is part of MetaModels/attribute_alias.
 *
 * (c) 2012-2023 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_alias
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2023 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_alias/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

declare(strict_types=1);

namespace MetaModels\AttributeCombinedValuesBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Types;

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
    private Connection $connection;

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
     * - these columns not type text.
     *
     * @return bool
     */
    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

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
        foreach ($langColumns as $tableName => $tableColumns) {
            foreach ($tableColumns as $tableColumn) {
                $this->fixColumn($tableName, $tableColumn);
                $message[] = $tableName . '.' . $tableColumn->getName();
            }
        }

        return new MigrationResult(true, 'Adjusted column(s): ' . \implode(', ', $message));
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
        $schemaManager = $this->connection->createSchemaManager();

        $result = [];
        foreach ($langColumns as $tableName => $tableColumnNames) {
            /** @var Column[] $columns */
            $columns = [];
            // The schema manager return the column list with lowercase keys, wo got to use the real names.
            $table = $schemaManager->introspectTable($tableName);
            foreach ($table->getColumns() as $column) {
                $columns[$column->getName()] = $column;
            }
            foreach ($tableColumnNames as $tableColumnName) {
                $column = ($columns[$tableColumnName] ?? null);
                if (null === $column) {
                    continue;
                }
                if (($column->getType() instanceof TextType)) {
                    continue;
                }
                $result[$tableName][$column->getName()] = $column;
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
            ->executeQuery()
            ->fetchAllAssociative();

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
     * @param string $tableName The name of the table.
     * @param Column $column    The column.
     *
     * @return void
     */
    private function fixColumn(string $tableName, Column $column): void
    {
        $manager = $this->connection->createSchemaManager();
        $table   = $manager->introspectTable($tableName);
        $updated = $manager->introspectTable($tableName);

        $updated->getColumn($column->getName())
            ->setType(TextType::getType(Types::TEXT))
            ->setNotnull(false)
            ->setDefault(null);

        $tableDiff = $manager->createComparator()->compareTables($table, $updated);

        $manager->alterTable($tableDiff);

        $this->connection->createQueryBuilder()
            ->update($tableName, 't')
            ->set('t.' . $column->getName(), 'null')
            ->where('t.' . $column->getName() . ' = ""')
            ->executeQuery();
    }
}
