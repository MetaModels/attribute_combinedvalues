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
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_combinedvalues/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\DcGeneral\Events\Table\Attribute\CombinedValues;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\IdSerializer;
use MenAtWork\MultiColumnWizard\Event\GetOptionsEvent;
use MetaModels\DcGeneral\Events\BaseSubscriber;

/**
 * Handle events for tl_metamodel_attribute.
 */
class Subscriber extends BaseSubscriber
{
    /**
     * Register all listeners.
     *
     * @return void
     */
    public function registerEventsInDispatcher()
    {
        $this
            ->addListener(
                GetOptionsEvent::NAME,
                [$this, 'getOptions']
            );
    }

    /**
     * Check if the event is intended for us.
     *
     * @param GetOptionsEvent $event The event to test.
     *
     * @return bool
     */
    private function isEventForMe(GetOptionsEvent $event)
    {
        $input = $event->getEnvironment()->getInputProvider();
        $type  = $event->getModel()->getProperty('type');

        if ($input->hasValue('type')) {
            $type = $input->getValue('type');
        }

        if (empty($type)) {
            $type = $event->getModel()->getProperty('type');
        }

        return
            ($event->getEnvironment()->getDataDefinition()->getName() === 'tl_metamodel_attribute')
            && ($type === 'combinedvalues')
            && ($event->getPropertyName() === 'combinedvalues_fields')
            && ($event->getSubPropertyName() === 'field_attribute');
    }

    /**
     * Retrieve the options for the attributes.
     *
     * @param GetOptionsEvent $event The event.
     *
     * @return void
     */
    public function getOptions(GetOptionsEvent $event)
    {
        if (!self::isEventForMe($event)) {
            return;
        }

        $model       = $event->getModel();
        $metaModelId = $model->getProperty('pid');
        if (!$metaModelId) {
            $metaModelId = IdSerializer::fromSerialized(
                $event->getEnvironment()->getInputProvider()->getValue('pid')
            )->getId();
        }

        $factory       = $this->getServiceContainer()->getFactory();
        $metaModelName = $factory->translateIdToMetaModelName($metaModelId);
        $metaModel     = $factory->getMetaModel($metaModelName);

        if (!$metaModel) {
            return;
        }

        $result = [];
        // Add meta fields.
        $result['meta'] = self::getMetaModelsSystemColumns();

        // Fetch all attributes except for the current attribute.
        foreach ($metaModel->getAttributes() as $attribute) {
            if ($attribute->get('id') === $model->getId()) {
                continue;
            }

            $result['attributes'][$attribute->getColName()] = \sprintf(
                '%s [%s]',
                $attribute->getName(),
                $attribute->get('type')
            );
        }

        $event->setOptions($result);
    }

    /**
     * Returns the global MetaModels System Columns (replacement for super global access).
     *
     * @return mixed Global MetaModels System Columns
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected static function getMetaModelsSystemColumns()
    {
        return $GLOBALS['METAMODELS_SYSTEM_COLUMNS'];
    }
}
