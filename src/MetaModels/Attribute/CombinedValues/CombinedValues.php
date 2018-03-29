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

namespace MetaModels\Attribute\CombinedValues;

use MetaModels\Attribute\BaseSimple;

/**
 * This is the MetaModelAttribute class for handling combined values.
 */
class CombinedValues extends BaseSimple
{
    /**
     * {@inheritdoc}
     */
    public function getSQLDataType()
    {
        return 'text NULL';
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSettingNames()
    {
        return array_merge(
            parent::getAttributeSettingNames(),
            [
                'combinedvalues_fields',
                'combinedvalues_format',
                'force_combinedvalues',
                'isunique',
                'mandatory',
                'filterable',
                'searchable',
                'sortable'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDefinition($arrOverrides = [])
    {
        $arrFieldDef = parent::getFieldDefinition($arrOverrides);

        $arrFieldDef['inputType'] = 'text';

        // We do not need to set mandatory, as we will automatically update our value when isunique is given.
        if ($this->get('isunique')) {
            $arrFieldDef['eval']['mandatory'] = false;
        }

        // If "force_combinedvalues" is true set alwaysSave and readonly to true.
        if ($this->get('force_combinedvalues')) {
            $arrFieldDef['eval']['alwaysSave'] = true;
            $arrFieldDef['eval']['readonly']   = true;
        }

        return $arrFieldDef;
    }

    /**
     * {@inheritdoc}
     */
    public function modelSaved($objItem)
    {
        // Combined values already defined and no update forced, get out!
        if ($objItem->get($this->getColName()) && (!$this->get('force_combinedvalues'))) {
            return;
        }

        $arrCombinedValues = [];
        foreach (deserialize($this->get('combinedvalues_fields')) as $strAttribute) {
            if ($this->isMetaField($strAttribute['field_attribute'])) {
                $strField            = $strAttribute['field_attribute'];
                $arrCombinedValues[] = $objItem->get($strField);
            } else {
                $arrValues           = $objItem->parseAttribute($strAttribute['field_attribute'], 'text', null);
                $arrCombinedValues[] = $arrValues['text'];
            }
        }

        $strCombinedValues = vsprintf($this->get('combinedvalues_format'), $arrCombinedValues);
        $strCombinedValues = trim($strCombinedValues);

        if ($this->get('isunique') && $this->searchFor($strCombinedValues)) {
            // Ensure uniqueness.
            $strBaseValue = $strCombinedValues;
            $arrIds       = [$objItem->get('id')];
            $intCount     = 2;
            while (array_diff($this->searchFor($strCombinedValues), $arrIds)) {
                $strCombinedValues = $strBaseValue . ' (' . ($intCount++) . ')';
            }
        }

        $this->setDataFor([$objItem->get('id') => $strCombinedValues]);
        $objItem->set($this->getColName(), $strCombinedValues);
    }

    /**
     * {@inheritdoc}
     */
    public function get($strKey)
    {
        if ($strKey == 'force_alias') {
            $strKey = 'force_combinedvalues';
        }

        return parent::get($strKey);
    }

    /**
     * Check if we have a meta field from metamodels.
     *
     * @param string $strField The selected value.
     *
     * @return boolean True => Yes we have | False => nope.
     */
    protected function isMetaField($strField)
    {
        $strField = trim($strField);

        if (in_array($strField, $this->getMetaModelsSystemColumns())) {
            return true;
        }

        return false;
    }

    /**
     * Returns the global MetaModels System Columns (replacement for super global access).
     *
     * @return mixed Global MetaModels System Columns
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function getMetaModelsSystemColumns()
    {
        return $GLOBALS['METAMODELS_SYSTEM_COLUMNS'];
    }
}
