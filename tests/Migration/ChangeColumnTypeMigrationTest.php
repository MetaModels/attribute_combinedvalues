<?php

/**
 * This file is part of MetaModels/attribute_alias.
 *
 * (c) 2012-2021 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_alias
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_alias/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

declare(strict_types=1);

namespace MetaModels\AttributeCombinedValuesBundle\Test\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Generator;
use MetaModels\AttributeCombinedValuesBundle\Migration\ChangeColumnTypeMigration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MetaModels\AttributeCombinedValuesBundle\Migration\ChangeColumnTypeMigration
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ChangeColumnTypeMigrationTest extends TestCase
{
    public function testName(): void
    {
        $connection = $this->createMock(Connection::class);
        $migration  = new ChangeColumnTypeMigration($connection);

        self::assertSame(
            'Change the column from varchar(255) to text in MetaModels "combinedvalues" attributes.',
            $migration->getName()
        );
    }

    public function runConfiguration(): Generator
    {
        yield 'required tables not exist' => [
            (object) [
                'requiredTablesExist' => false,
                'shouldRun'           => false,
                'attributeConfigured' => false
            ]
        ];

        yield 'attribute not configured' => [
            (object) [
                'requiredTablesExist' => true,
                'shouldRun'           => false,
                'attributeConfigured' => false
            ]
        ];

        yield 'attribute is configured' => [
            (object) [
                'requiredTablesExist' => true,
                'shouldRun'           => false,
                'attributeConfigured' => true
            ]
        ];

        yield 'columns migrated' => [
            (object) [
                'requiredTablesExist' => true,
                'shouldRun'           => true,
                'attributeConfigured' => true
            ]
        ];
    }

    /**
     * @dataProvider runConfiguration
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRun(object $configuration): void
    {
        $connection = $this->createMock(Connection::class);
        $plattform  = $this->getMockBuilder(AbstractPlatform::class)->disableOriginalConstructor()->getMock();
        $manager    = $this
            ->getMockBuilder(AbstractSchemaManager::class)
            ->setConstructorArgs([$connection, $plattform])
            ->onlyMethods(['listTableNames', 'introspectTable', 'alterTable'])
            ->getMockForAbstractClass();

        $manager
            ->expects(self::once())
            ->method('listTableNames')
            ->willReturn(
                $configuration->requiredTablesExist
                    ? ['tl_metamodel', 'tl_metamodel_attribute', 'mm_table_1', 'mm_table_2']
                    : []
            );

        $connection
            ->expects(
                $configuration->attributeConfigured ? self::exactly($configuration->shouldRun ? 7 : 2) : self::once()
            )
            ->method('createSchemaManager')
            ->willReturn($manager);

        $queryBuilderExecuted = 0;
        if ($configuration->requiredTablesExist) {
            $attributes = [
                ['metamodel' => 'mm_table_2', 'attribute' => 'normal'],
                ['metamodel' => 'mm_table_1', 'attribute' => 'camelCase'],
                ['metamodel' => 'mm_table_1', 'attribute' => 'normal'],
                ['metamodel' => 'mm_table_2', 'attribute' => 'camelCase'],
                ['metamodel' => 'mm_table_2', 'attribute' => 'columnnotexist'],
                ['metamodel' => 'mm_table_2', 'attribute' => 'columnNotExist']
            ];
            $result = $this->getMockBuilder(Result::class)->disableOriginalConstructor()->getMock();
            $result
                ->expects($configuration->shouldRun ? self::exactly(2)  : self::once())
                ->method('fetchAllAssociative')
                ->willReturn($configuration->attributeConfigured ? $attributes : []);

            $queryBuilder = $this->createMock(QueryBuilder::class);
            $queryBuilder
                ->expects($configuration->shouldRun ? self::exactly(4)  : self::never())
                ->method('update')
                ->willReturnCallback(function (string $table, string $alias) use ($queryBuilder) {
                    static $expected = [
                        ['mm_table_2', 't'],
                        ['mm_table_2', 't'],
                        ['mm_table_1', 't'],
                        ['mm_table_1', 't'],
                    ];
                    static $invocationCount = 0;

                    self::assertSame($expected[$invocationCount][0] ?? null, $table);
                    self::assertSame($expected[$invocationCount][1] ?? null, $alias);
                    $invocationCount++;

                    return $queryBuilder;
                });
            $queryBuilder
                ->expects($configuration->shouldRun ? self::exactly(4)  : self::never())
                ->method('set')
                ->willReturnCallback(function (string $parameter, string $value) use ($queryBuilder) {
                    static $expected = [
                        ['t.normal', 'null'],
                        ['t.camelCase', 'null'],
                        ['t.camelCase', 'null'],
                        ['t.normal', 'null'],
                    ];
                    static $invocationCount = 0;

                    self::assertSame($expected[$invocationCount][0] ?? null, $parameter);
                    self::assertSame($expected[$invocationCount][1] ?? null, $value);
                    $invocationCount++;

                    return $queryBuilder;
                });
            $queryBuilder
                ->expects($configuration->shouldRun ? self::exactly(2)  : self::once())
                ->method('select')
                ->with('metamodel.tableName AS metamodel', 'attribute.colName AS attribute')
                ->willReturn($queryBuilder);
            $queryBuilder
                ->expects($configuration->shouldRun ? self::exactly(2)  : self::once())
                ->method('from')
                ->with('tl_metamodel_attribute', 'attribute')
                ->willReturn($queryBuilder);
            $queryBuilder
                ->expects($configuration->shouldRun ? self::exactly(2)  : self::once())
                ->method('leftJoin')
                ->with('attribute', 'tl_metamodel', 'metamodel', 'attribute.pid = metamodel.id')
                ->willReturn($queryBuilder);
            $queryBuilder
                ->expects($configuration->shouldRun ? self::exactly(6) : self::once())
                ->method('where')
                ->willReturnCallback(function (string $restriction) use ($queryBuilder) {
                    static $expected = [
                        ['attribute.type=:type'],
                        ['attribute.type=:type'],
                        ['t.normal = ""'],
                        ['t.camelCase = ""'],
                        ['t.camelCase = ""'],
                        ['t.normal = ""']
                    ];
                    static $invocationCount = 0;

                    self::assertSame($expected[$invocationCount][0] ?? '', $restriction);
                    $invocationCount++;

                    return $queryBuilder;
                });
            $queryBuilder
                ->expects($configuration->shouldRun ? self::exactly(2)  : self::once())
                ->method('setParameter')
                ->with('type', 'combinedvalues')
                ->willReturn($queryBuilder);
            $queryBuilder
                ->expects($configuration->shouldRun ? self::exactly(6)  : self::once())
                ->method('executeQuery')
                ->willReturnCallback(
                    function () use (&$queryBuilderExecuted, $result) {
                        $queryBuilderExecuted++;
                        if ($queryBuilderExecuted <= 2) {
                            return $result;
                        }

                        return $this->getMockBuilder(Result::class)->disableOriginalConstructor()->getMock();
                    }
                );

            $connection
                ->expects($configuration->shouldRun ? self::exactly(6)  : self::once())
                ->method('createQueryBuilder')
                ->willReturn($queryBuilder);
        }

        $tables = [];
        if ($configuration->requiredTablesExist && $configuration->attributeConfigured) {
            $columnDefault = $configuration->shouldRun ? '' : null;
            $columnType    = $configuration->shouldRun ? new StringType() : new TextType();
            $columnLength  = $configuration->shouldRun ? 255 : MySQLPlatform::LENGTH_LIMIT_TEXT;
            $columnNotNull = $configuration->shouldRun ? true : false;
            $tables = [
                'mm_table_1' => (new Table('mm_table_2', [
                    (new Column('normal', $columnType))->setDefault($columnDefault)
                        ->setLength($columnLength)
                        ->setNotnull($columnNotNull),
                    (new Column('camelCase', $columnType))->setDefault($columnDefault)
                        ->setLength($columnLength)
                        ->setNotnull($columnNotNull),
                ])),
                'mm_table_2' => (new Table('mm_table_2', [
                    (new Column('normal', $columnType))->setDefault($columnDefault)
                        ->setLength($columnLength)
                        ->setNotnull($columnNotNull),
                    (new Column('camelCase', $columnType))->setDefault($columnDefault)
                        ->setLength($columnLength)
                        ->setNotnull($columnNotNull),
                ])),
            ];
        }
        $manager
            ->method('introspectTable')
            ->willReturnCallback(function (string $tableName) use ($tables) {
                $return = $tables[$tableName] ?? null;
                self::assertNotNull($return);
                return $return;
            });

        $migration = new ChangeColumnTypeMigration($connection);
        self::assertSame($configuration->shouldRun, $migration->shouldRun());
        self::assertSame($configuration->requiredTablesExist ? 1 : 0, $queryBuilderExecuted);

        if (!$configuration->shouldRun) {
            return;
        }

        $migrationResult = $migration->run();
        self::assertTrue($migrationResult->isSuccessful());
        self::assertSame(
            'Adjusted column(s): mm_table_2.normal, mm_table_2.camelCase, mm_table_1.camelCase, mm_table_1.normal',
            $migrationResult->getMessage()
        );
        self::assertSame(6, $queryBuilderExecuted);
    }
}
