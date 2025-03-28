<?php

/**
 * This file is part of MetaModels/attribute_combinedvalues.
 *
 * (c) 2012-2021 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_combinedvalues
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_combinedvalues/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeCombinedValuesBundle\Test\Attribute;

use Doctrine\DBAL\Connection;
use MetaModels\AttributeCombinedValuesBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeCombinedValuesBundle\Attribute\CombinedValues;
use MetaModels\Helper\TableManipulator;
use MetaModels\IMetaModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test the attribute factory.
 *
 * @covers \MetaModels\AttributeCombinedValuesBundle\Attribute\AttributeTypeFactory
 */
class CombinedValuesAttributeTypeFactoryTest extends TestCase
{
    /**
     * Mock a MetaModel.
     *
     * @param string $tableName        The table name.
     * @param string $language         The language.
     * @param string $fallbackLanguage The fallback language.
     *
     * @return IMetaModel
     */
    protected function mockMetaModel($tableName, $language, $fallbackLanguage)
    {
        $metaModel = $this->getMockForAbstractClass(IMetaModel::class);
        $metaModel
            ->method('getTableName')
            ->willReturn($tableName);
        $metaModel
            ->method('getActiveLanguage')
            ->willReturn($language);
        $metaModel
            ->method('getFallbackLanguage')
            ->willReturn($fallbackLanguage);

        return $metaModel;
    }

    /**
     * Mock the database connection.
     *
     * @return MockObject|Connection
     */
    private function mockConnection()
    {
        return $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Mock the table manipulator.
     *
     * @param Connection $connection The database connection mock.
     *
     * @return TableManipulator|MockObject
     */
    private function mockTableManipulator(Connection $connection)
    {
        return $this->getMockBuilder(TableManipulator::class)
            ->setConstructorArgs([$connection, []])
            ->getMock();
    }

    /**
     * Test creation of an translated select.
     *
     * @return void
     */
    public function testCreateInstance()
    {
        $connection = $this->mockConnection();
        $factory    = new AttributeTypeFactory($connection, $this->mockTableManipulator($connection));
        $values     = [
            'force_combinedvalues'  => '',
            'combinedvalues_fields' => \serialize(['title']),
            'combinedvalues_format' => ''
        ];
        $attribute  = $factory->createInstance(
            $values,
            $this->mockMetaModel('mm_test', 'de', 'en')
        );

        $check                          = $values;
        $check['combinedvalues_fields'] = \unserialize($check['combinedvalues_fields']);

        self::assertInstanceOf(CombinedValues::class, $attribute);

        foreach ($check as $key => $value) {
            self::assertEquals($value, $attribute->get($key), $key);
        }
    }
}
