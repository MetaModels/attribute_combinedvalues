<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package     MetaModels
 * @subpackage  AttributeCombinedValues
 * @author      Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author      Andreas Isaak <andy.jared@googlemail.com>
 * @author      David Greminger <david.greminger@1up.io>
 * @author      Stefan Heimes <stefan_heimes@hotmail.com>
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

namespace MetaModels\Attribute\CombinedValues;

use MetaModels\Attribute\BaseSimple;

/**
 * This is the MetaModelAttribute class for handling combined values.
 *
 * @package    MetaModels
 * @subpackage AttributeCombinedValues
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Stefan Heimes <cms@men-at-work.de>
 */
class CombinedValues extends BaseSimple
{
    /**
     * {@inheritdoc}
     */
    public function getSQLDataType()
    {
        return 'varchar(255) NOT NULL default \'\'';
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSettingNames()
    {
        return array_merge(parent::getAttributeSettingNames(), array(
            'combinedvalues_fields',
            'combinedvalues_format',
            'force_combinedvalues',
            'isunique',
            'mandatory',
            'filterable',
            'searchable',
            'sortable'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDefinition($arrOverrides = array())
    {
        $arrFieldDef = parent::getFieldDefinition($arrOverrides);

        $arrFieldDef['inputType'] = 'text';

        // We do not need to set mandatory, as we will automatically update our value when isunique is given.
        if ($this->get('isunique')) {
            $arrFieldDef['eval']['mandatory'] = false;
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

        $arrCombinedValues = '';
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
            $arrIds       = array($objItem->get('id'));
            $intCount     = 2;
            while (array_diff($this->searchFor($strCombinedValues), $arrIds)) {
                $strCombinedValues = $strBaseValue . ' (' . ($intCount++) . ')';
            }
        }

        $this->setDataFor(array($objItem->get('id') => $strCombinedValues));
        $objItem->set($this->getColName(), $strCombinedValues);
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
