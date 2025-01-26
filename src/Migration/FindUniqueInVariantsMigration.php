<?php

/**
 * This file is part of MetaModels/core.
 *
 * (c) 2012-2025 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/core
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2025 The MetaModels team.
 * @license    https://github.com/MetaModels/core/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

declare(strict_types=1);

namespace MetaModels\AttributeCombinedValuesBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

use function array_intersect;
use function array_map;
use function array_values;
use function count;
use function implode;
use function sprintf;

/**
 * This migration find unsupported combination of variant with isunique and write a notice.
 */
class FindUniqueInVariantsMigration extends AbstractMigration
{
    /** @var list<string> */
    private array $existsCache = [];

    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * Return the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Find unsupported combination of option variant with unique.';
    }

    /**
     * Must only run if:
     * - find unsupported combination of variant with isunique.
     *
     * @return bool
     */
    public function shouldRun(): bool
    {
        if (!$this->tablesExist(['tl_metamodel', 'tl_metamodel_attribute'])) {
            return false;
        }

        if ($this->findUniqueOrForceInVariants()) {
            return true;
        }

        return false;
    }

    /**
     * Find unsupported combination of variant with isunique.
     *
     * @return MigrationResult
     */
    public function run(): MigrationResult
    {
        if (($modleList = $this->findUniqueOrForceInVariants())) {
            return new MigrationResult(
                false,
                sprintf(
                    'We find unsupported combination of variant with unique in models:' . PHP_EOL .
                    '%s Please check settings of attribute "Combined values". This CAN NOT be done automatically!',
                    $modleList
                )
            );
        }

        return new MigrationResult(true, 'Nothing to do.');
    }

    /**
     * Find unsupported combination of variant with isunique or force_alias.
     *
     * @return string
     */
    private function findUniqueOrForceInVariants(): string
    {
        $poorCombinations = $this
            ->connection
            ->createQueryBuilder()
            ->select('metamodel.*')
            ->from('tl_metamodel_attribute', 'attribute')
            ->leftJoin('attribute', 'tl_metamodel', 'metamodel', 'attribute.pid = metamodel.id')
            ->where('metamodel.varsupport=1')
            ->andWhere('attribute.type=:type')
            ->andWhere('attribute.isvariant!=1 ')
            ->andwhere('attribute.isunique=1')
            ->setParameter('type', 'combinedvalues')
            ->groupBy('metamodel.id')
            ->executeQuery()
            ->fetchAllAssociative();

        $result = [];
        foreach ($poorCombinations as $poorCombination) {
            $result[] = sprintf(' - "%s" [%s]' . PHP_EOL, $poorCombination['name'], $poorCombination['tableName']);
        }

        return implode(', ', $result);
    }

    private function tablesExist(array $tableNames): bool
    {
        if ([] === $this->existsCache) {
            $this->existsCache = array_values($this->connection->createSchemaManager()->listTableNames());
        }

        return count($tableNames) === count(array_intersect($tableNames, array_map('strtolower', $this->existsCache)));
    }
}
