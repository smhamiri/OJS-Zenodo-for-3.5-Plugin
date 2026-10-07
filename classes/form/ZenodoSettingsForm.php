<?php

/**
 * @file plugins/generic/zenodo/classes/form/ZenodoSettingsForm.php
 *
 * Zenodo settings form for OJS 3.5.0-5.
 */

namespace APP\plugins\generic\zenodo\classes\form;

use APP\core\Application;
use APP\plugins\generic\zenodo\ZenodoExportPlugin;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorCustom;
use PKP\form\validation\FormValidatorPost;
use PKP\plugins\Plugin;

class ZenodoSettingsForm extends Form
{
    public int $contextId;
    public Plugin $plugin;
    public string $genericPluginName = 'zenodoplugin';

    public function __construct(Plugin $plugin, int $contextId)
    {
        $this->contextId = $contextId;
        $this->plugin = $plugin;

        parent::__construct(
            $plugin->getTemplateResource('settingsForm.tpl')
        );

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));

        $this->addCheck(new FormValidatorCustom(
            $this,
            'community',
            'optional',
            'plugins.importexport.zenodo.register.error.communityError',
            function ($community) {
                // An empty community is allowed.
                $community = strtolower(trim((string) $community));

                if ($community === '') {
                    return true;
                }

                $communityId = $this->getCommunityId(
                    $community,
                    $this->contextId,
                    $this->plugin
                );

                if (is_array($communityId)) {
                    error_log(
                        __($communityId[0], ['param' => $communityId[1]])
                    );
                    return false;
                }

                return $communityId === true;
            }
        ));
    }

    public function setGenericPluginName(string $name): void
    {
        $this->genericPluginName = $name;
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = \APP\template\TemplateManager::getManager($request);
        $templateMgr->assign('genericPluginName', $this->genericPluginName);

        return parent::fetch($request, $template, $display);
    }

    public function getContextId(): int
    {
        return $this->contextId;
    }

    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }

    public function initData(): void
    {
        foreach ($this->getFormFields() as $fieldName => $fieldType) {
            $value = $this->plugin->getSetting(
                $this->contextId,
                $fieldName
            );

            if ($fieldType === 'bool') {
                $value = (bool) $value;
            }

            $this->setData($fieldName, $value);
        }
    }

    public function readInputData(): void
    {
        $this->readUserVars(array_keys($this->getFormFields()));
    }

    public function execute(...$functionArgs): void
    {
        parent::execute(...$functionArgs);

        foreach ($this->getFormFields() as $fieldName => $fieldType) {
            $value = $this->getData($fieldName);

            if ($fieldType === 'bool') {
                $value = (bool) $value;
            }

            if ($fieldName === 'community') {
                $value = strtolower(trim((string) $value));

                $this->plugin->updateSetting(
                    $this->contextId,
                    $fieldName,
                    $value,
                    'string'
                );

                if ($value === '') {
                    $this->plugin->updateSetting(
                        $this->contextId,
                        'communityId',
                        '',
                        'string'
                    );
                }
            } else {
                $this->plugin->updateSetting(
                    $this->contextId,
                    $fieldName,
                    $value,
                    $fieldType
                );
            }
        }
    }

    /**
     * Only the API key is required for the plugin to be considered configured.
     */
    public function isOptional(string $settingName): bool
    {
        return $settingName !== 'apiKey';
    }

    public function getFormFields(): array
    {
        return [
            'apiKey' => 'string',
            'automaticPublishing' => 'bool',
            'automaticPublishingCommunity' => 'bool',
            'automaticRegistration' => 'bool',
            'community' => 'string',
            'mintDoi' => 'bool',
            'testMode' => 'bool',
        ];
    }

    /**
     * Find and store the Zenodo community ID.
     */
    public function getCommunityId(
        string $communityName,
        int $contextId,
        Plugin $plugin
    ): array|bool {
        $communityName = strtolower(trim($communityName));

        if ($communityName === '') {
            return true;
        }

        $context = Application::getContextDAO()->getById($contextId);

        if (!$context) {
            return [
                'plugins.importexport.zenodo.api.error.communityIdError',
                'The journal context could not be found.'
            ];
        }

        $httpClient = Application::get()->getHttpClient();

        $url = $plugin->isTestMode($context)
            ? ZenodoExportPlugin::ZENODO_API_URL_DEV
            : ZenodoExportPlugin::ZENODO_API_URL;

        $communityUrl = $url . 'communities/' .
            rawurlencode($communityName);

        try {
            $response = $httpClient->request(
                'GET',
                $communityUrl
            );

            $body = json_decode(
                (string) $response->getBody(),
                true
            );

            if (
                $response->getStatusCode() ===
                ZenodoExportPlugin::ZENODO_API_OK
            ) {
                if (is_array($body) && !empty($body['id'])) {
                    $plugin->updateSetting(
                        $contextId,
                        'communityId',
                        (string) $body['id'],
                        'string'
                    );

                    return true;
                }

                return [
                    'plugins.importexport.zenodo.api.error.communityIdError',
                    'No community ID found in the Zenodo API response.'
                ];
            }

            return [
                'plugins.importexport.zenodo.api.error.communityIdError',
                'Zenodo returned HTTP status ' .
                    $response->getStatusCode()
            ];
        } catch (GuzzleException | Exception $e) {
            return [
                'plugins.importexport.zenodo.api.error.communityIdError',
                $e->getCode() . ' - ' . $e->getMessage()
            ];
        }
    }
}