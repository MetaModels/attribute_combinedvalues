<?php

/**
 * This file is part of MetaModels/attribute_alias.
 *
 * (c) 2012-2025 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_alias
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2025 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_alias/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeCombinedValuesBundle\EventListener;

use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\BuildWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use ContaoCommunityAlliance\DcGeneral\Data\ModelInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\ContainerInterface;
use ContaoCommunityAlliance\DcGeneral\Event\ValidateModelEvent;
use ContaoCommunityAlliance\DcGeneral\InputProviderInterface;
use MetaModels\IFactory;
use MetaModels\IMetaModel;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This class check valid combinations of checkboxes.
 */
class SetDefaultValuesAtCheckboxesListener
{
    /**
     * Create a new instance.
     */
    public function __construct(
        private readonly RequestScopeDeterminator $scopeDeterminator,
        private readonly IFactory $factory,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Check valid combination of checkboxes 'isvariant' with 'isunique' -
     * combination of active options 'isvariant' with 'isunique' is not supportet.
     *
     * @param ValidateModelEvent $event The event.
     *
     * @return void
     */
    public function onValidateModel(ValidateModelEvent $event): void
    {
        $model = $event->getModel();

        $dataDefinition = $event->getEnvironment()->getDataDefinition();
        assert($dataDefinition instanceof ContainerInterface);

        // Next check only if 'isvariant' not checked and MetaModel is variant.
        if (
            false === $this->scopeDeterminator->currentScopeIsBackend()
            || 'combinedvalues' !== $model->getProperty('type')
            || 'tl_metamodel_attribute' !== $dataDefinition->getName()
        ) {
            return;
        }

        $metaModelId = $model->getProperty('pid');
        if (!$metaModelId) {
            $inputProvider = $event->getEnvironment()->getInputProvider();
            assert($inputProvider instanceof InputProviderInterface);
            $metaModelId = ModelId::fromSerialized($inputProvider->getParameter('pid'))->getId();
        }

        $metaModelName = $this->factory->translateIdToMetaModelName($metaModelId);
        $metaModel     = $this->factory->getMetaModel($metaModelName);
        assert($metaModel instanceof IMetaModel);

        if (
            !$metaModel->hasVariants()
            || $model->getProperty('isvariant')
        ) {
            return;
        }

        if ($model->getProperty('isunique')) {
            $errorMessage = $this->translator->trans('isunique.variant_error', [], 'tl_metamodel_attribute');
            $event->getPropertyValueBag()->markPropertyValueAsInvalid('isunique', [$errorMessage]);
        }
    }
}
