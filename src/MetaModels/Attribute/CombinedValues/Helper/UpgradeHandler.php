<?php

/**
 * This file is part of MetaModels/attribute_combinedvalues.
 *
 * (c) 2012-2018 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeCombinedValues
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_combinedvalues/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\Attribute\CombinedValues\Helper;

use Contao\Database;

/**
 * Upgrade handler class that changes structural changes in the database.
 * This should rarely be necessary but sometimes we need it.
 */
class UpgradeHandler
{
    /**
     * The database to use.
     *
     * @var Database
     */
    private $database;

    /**
     * Create a new instance.
     *
     * @param Database $database The database instance to use.
     */
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Perform all upgrade steps.
     *
     * @return void
     */
    public function perform()
    {
        $this->ensureCorrectColumnType();
    }

    /**
     * Ensure that the column types are correct.
     *
     * This was changed in:
     *   https://github.com/MetaModels/attribute_combinedvalues/commit/a32fbf63bcb925f88da2f1ff25ddb6e30238df17
     * Solves issue:
     *   https://github.com/MetaModels/attribute_combinedvalues/issues/7
     *
     * @return void
     */
    private function ensureCorrectColumnType()
    {
        if (!$this->database->tableExists('tl_metamodel_attribute')) {
            return;
        }

        $attributes = $this
            ->database
            ->prepare(
                'SELECT metamodel.tableName, attribute.colname
                FROM tl_metamodel_attribute AS attribute
                LEFT JOIN tl_metamodel AS metamodel
                ON (metamodel.id=attribute.pid)
                WHERE attribute.type=?'
            )
            ->execute('combinedvalues');

        while ($attributes->next()) {
            $this
                ->database
                ->execute(
                    \sprintf(
                        'ALTER TABLE %1$s CHANGE COLUMN %2$s %2$s %3$s',
                        $attributes->tableName,
                        $attributes->colname,
                        'text NULL'
                    )
                );
        }
    }
}
