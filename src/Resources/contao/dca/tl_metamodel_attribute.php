<?php

/**
 * This file is part of MetaModels/attribute_combinedvalues.
 *
 * (c) 2012-2016 The MetaModels team.
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
 * @copyright  2012-2016 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_combinedvalues/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

/**
 * Table tl_metamodel_attribute
 */

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['combinedvalues extends _simpleattribute_'] = array
(
    '+advanced' => array('force_combinedvalues'),
    '+display'  => array('combinedvalues_format after description', 'combinedvalues_fields')
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['combinedvalues_fields'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['combinedvalues_fields'],
    'exclude'                 => true,
    'inputType'               => 'multiColumnWizard',
    'eval'                    => array
    (
        'tl_class'     => 'clx',
        'columnFields' => array
        (
            'field_attribute' => array
            (
                'label'                 => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['field_attribute'],
                'exclude'               => true,
                'inputType'             => 'select',
                'reference'             => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_values'],
                'eval' => array
                    (
                        'style'         => 'width:600px',
                        'chosen'        => 'true',
                    )
            ),
        ),
    ),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['force_combinedvalues'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['force_combinedvalues'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class' => 'w50'),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['combinedvalues_format'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['combinedvalues_format'],
    'exclude'                 => true,
    'inputType'               => 'text',
    'eval'                    => array('mandatory' => true, 'tl_class' => 'long'),
);
