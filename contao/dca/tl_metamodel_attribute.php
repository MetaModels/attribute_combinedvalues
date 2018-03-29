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
 * @author     Andreas Isaak <andy.jared@googlemail.com>
 * @author     David Greminger <david.greminger@1up.io>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2018 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_combinedvalues/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

/**
 * Table tl_metamodel_attribute
 */

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['combinedvalues extends _simpleattribute_'] = [
    '+advanced' => ['force_combinedvalues'],
    '+display'  => ['combinedvalues_format after description', 'combinedvalues_fields']
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['combinedvalues_fields'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['combinedvalues_fields'],
    'exclude'   => true,
    'inputType' => 'multiColumnWizard',
    'sql'       => 'blob NULL',
    'eval'      => [
        'tl_class'     => 'clx',
        'columnFields' => [
            'field_attribute' => [
                'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['field_attribute'],
                'exclude'   => true,
                'inputType' => 'select',
                'reference' => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_values'],
                'eval'      => [
                    'style'  => 'width:600px',
                    'chosen' => 'true',
                ]
            ],
        ],
    ],
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['force_combinedvalues'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['force_combinedvalues'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'sql'       => 'char(1) NOT NULL default \'\'',
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['combinedvalues_format'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['combinedvalues_format'],
    'exclude'   => true,
    'inputType' => 'text',
    'sql'       => 'text NULL',
    'eval'      => ['mandatory' => true, 'tl_class' => 'long'],
];
