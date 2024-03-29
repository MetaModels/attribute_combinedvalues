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

namespace MetaModels\AttributeCombinedValuesBundle\Test;

use MetaModels\AttributeCombinedValuesBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeCombinedValuesBundle\Attribute\CombinedValues;
use MetaModels\AttributeCombinedValuesBundle\EventListener\GetOptionsListener;
use PHPUnit\Framework\TestCase;

/**
 * This class tests if the deprecated autoloader works.
 *
 * @covers \MetaModels\AttributeCombinedValuesBundle\Attribute\CombinedValues
 * @covers \MetaModels\AttributeCombinedValuesBundle\Attribute\AttributeTypeFactory
 * @covers \MetaModels\AttributeCombinedValuesBundle\EventListener\GetOptionsListener
 */
class DeprecatedAutoloaderTest extends TestCase
{
    /**
     * Aliases of old classes to the new one.
     *
     * @var array
     */
    private static $classes = [
        'MetaModels\Attribute\CombinedValues\CombinedValues' => CombinedValues::class,
        'MetaModels\Attribute\CombinedValues\AttributeTypeFactory' => AttributeTypeFactory::class,
        'MetaModels\DcGeneral\Events\Table\Attribute\CombinedValues\Subscriber' => GetOptionsListener::class
    ];

    /**
     * Provide the alias class map.
     *
     * @return array
     */
    public function provideAliasClassMap()
    {
        $values = [];

        foreach (static::$classes as $alias => $class) {
            $values[] = [$alias, $class];
        }

        return $values;
    }

    /**
     * Test if the deprecated classes are aliased to the new one.
     *
     * @param string $oldClass Old class name.
     * @param string $newClass New class name.
     *
     * @return void
     *
     * @dataProvider provideAliasClassMap
     */
    public function testDeprecatedClassesAreAliased($oldClass, $newClass)
    {
        self::assertTrue(\class_exists($oldClass), \sprintf('Class alias "%s" is not found.', $oldClass));

        $oldClassReflection = new \ReflectionClass($oldClass);
        $newClassReflection = new \ReflectionClass($newClass);

        self::assertSame($newClassReflection->getFileName(), $oldClassReflection->getFileName());
    }
}
