<?php

namespace APP\plugins\generic\zenodo;

use PKP\plugins\GenericPlugin;
use PKP\plugins\PluginRegistry;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\core\JSONMessage;
use APP\plugins\generic\zenodo\classes\form\ZenodoSettingsForm;

class ZenodoPlugin extends GenericPlugin
{
    protected ?ZenodoExportPlugin $exportPlugin = null;

    public function register($category, $path, $mainContextId = null)
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }

        if ($this->getEnabled()) {
            $this->exportPlugin = new ZenodoExportPlugin();

            PluginRegistry::register(
                'importexport',
                $this->exportPlugin,
                $this->getPluginPath()
            );

            $registeredPlugin = PluginRegistry::getPlugin(
                'importexport',
                'ZenodoExportPlugin'
            );

            if ($registeredPlugin instanceof ZenodoExportPlugin) {
                $this->exportPlugin = $registeredPlugin;
            }
        }

        return true;
    }

    public function getDisplayName(): string
    {
        return __('plugins.generic.zenodo.displayName');
    }

    public function getDescription(): string
    {
        return __('plugins.generic.zenodo.description');
    }

    public function getActions($request, $verb)
    {
        $actions = parent::getActions($request, $verb);

        if (!$this->getEnabled()) {
            return $actions;
        }

        $router = $request->getRouter();

        $settingsAction = new LinkAction(
            'settings',
            new AjaxModal(
                $router->url(
                    $request,
                    null,
                    null,
                    'manage',
                    null,
                    [
                        'verb' => 'settings',
                        'plugin' => $this->getName(),
                        'category' => 'generic'
                    ]
                ),
                $this->getDisplayName()
            ),
            __('manager.plugins.settings'),
            null
        );

        array_unshift($actions, $settingsAction);

        return $actions;
    }

    public function manage($args, $request)
    {
        if ($request->getUserVar('verb') === 'settings') {
            $context = $request->getContext();

            if (!$context || !$this->getEnabled()) {
                return new JSONMessage(false);
            }

            if (!$this->exportPlugin) {
                $plugin = PluginRegistry::getPlugin(
                    'importexport',
                    'ZenodoExportPlugin'
                );

                if ($plugin instanceof ZenodoExportPlugin) {
                    $this->exportPlugin = $plugin;
                }
            }

            if (!$this->exportPlugin) {
                error_log(
                    'Zenodo: ZenodoExportPlugin is not registered.'
                );
                return new JSONMessage(false);
            }

            $form = new ZenodoSettingsForm(
                $this->exportPlugin,
                (int) $context->getId()
            );

            $form->setGenericPluginName($this->getName());

            if ($request->getUserVar('save')) {
                $form->readInputData();

                if ($form->validate()) {
                    $form->execute();

                    return new JSONMessage(true);
                }
            } else {
                $form->initData();
            }

            return new JSONMessage(true, $form->fetch($request));
        }

        return parent::manage($args, $request);
    }

    public function getContextSpecificPluginSettingsFile(): string
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    public function getInstallSitePluginSettingsFile(): string
    {
        return $this->getPluginPath() . '/settings.xml';
    }
}