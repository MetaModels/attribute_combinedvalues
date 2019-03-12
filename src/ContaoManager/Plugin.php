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

namespace MetaModels\AttributeCombinedValuesBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use MetaModels\AttributeCombinedValuesBundle\MetaModelsAttributeCombinedValuesBundle;
use MetaModels\CoreBundle\MetaModelsCoreBundle;

/**
 * Contao Manager plugin.
 */
class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(MetaModelsAttributeCombinedValuesBundle::class)
                ->setLoadAfter(
                    [
                        MetaModelsCoreBundle::class
                    ]
                )
                ->setReplace(['metamodelsattribute_combinedvalues'])
        ];
    }
}
