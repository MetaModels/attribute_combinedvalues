<?php

/**
 * This file is part of MetaModels/attribute_combinedvalues.
 *
 * (c) 2012-2019 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_combinedvalues
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_combinedvalues/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

use MetaModels\AttributeCombinedValuesBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeCombinedValuesBundle\Attribute\CombinedValues;
use MetaModels\AttributeCombinedValuesBundle\EventListener\GetOptionsListener;
use MetaModels\AttributeCombinedValuesBundle\Helper\UpgradeHandler;

// This hack is to load the "old locations" of the classes.
spl_autoload_register(
    function ($class) {
        static $classes = [
            'MetaModels\Attribute\CombinedValues\CombinedValues' => CombinedValues::class,
            'MetaModels\Attribute\CombinedValues\AttributeTypeFactory' => AttributeTypeFactory::class,
            'MetaModels\Attribute\CombinedValues\Helper\UpgradeHandler' => UpgradeHandler::class,
            'MetaModels\DcGeneral\Events\Table\Attribute\CombinedValues\Subscriber' => GetOptionsListener::class
        ];

        if (isset($classes[$class])) {
            // @codingStandardsIgnoreStart Silencing errors is discouraged
            @trigger_error('Class "' . $class . '" has been renamed to "' . $classes[$class] . '"', E_USER_DEPRECATED);
            // @codingStandardsIgnoreEnd

            if (!class_exists($classes[$class])) {
                spl_autoload_call($class);
            }

            class_alias($classes[$class], $class);
        }
    }
);
