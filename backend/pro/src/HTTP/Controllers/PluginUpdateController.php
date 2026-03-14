<?php

namespace BitApps\Utils\HTTP\Controllers;

// Prevent direct script access
if (!defined('ABSPATH')) {
    exit;
}

use Automatic_Upgrader_Skin;
use BitApps\Utils\PluginCommonConfig;
use BitApps\Utils\ProPluginUpdater;
use Plugin_Upgrader;
use stdClass;

final class PluginUpdateController
{
    public function updatePlugin()
    {
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        $pluginBaseName = $this->getUpdatePluginBaseName();

        $updatePlugins = $this->normalizeUpdatePluginsCache(get_site_transient('update_plugins'));

        if (!isset($updatePlugins->response[$pluginBaseName]) && $pluginBaseName === PluginCommonConfig::getFreePluginBaseName()) {
            include_once ABSPATH . 'wp-includes/update.php';
            wp_update_plugins();

            $updatePlugins = $this->normalizeUpdatePluginsCache(get_site_transient('update_plugins'));
        }


        $updatePlugins = $this->checkAndUpdateProPluginInCache($pluginBaseName, $updatePlugins);

        if (isset($updatePlugins->response[$pluginBaseName])) {
            $upgrader = (new Plugin_Upgrader(new Automatic_Upgrader_Skin()));

            $pluginUpgraded = $upgrader->upgrade($pluginBaseName);

            if (is_wp_error($pluginUpgraded)) {
                return 'Error updating plugin: ' . $pluginUpgraded->get_error_message();
            }

            $pluginActivated = activate_plugin($pluginBaseName);

            if (is_wp_error($pluginActivated)) {
                return 'Plugin updated successfully! But failed to activate plugin.';
            }

            return 'Plugin updated and activated successfully!';
        }

        return 'No updates available for your plugin.';
    }

    public function isPluginUpdateAvailable()
    {
        $latestVersion = null;

        $freePluginBaseName = PluginCommonConfig::getFreePluginBaseName();

        $updatedPlugins = $this->normalizeUpdatePluginsCache(get_site_transient('update_plugins'));

        if (isset($updatedPlugins->response[$freePluginBaseName])) {
            $latestVersion = $updatedPlugins->response[$freePluginBaseName]->new_version;
        }

        return wp_send_json_success(
            [
                'latest_version' => $latestVersion,
            ]
        );
    }

    private function getUpdatePluginBaseName()
    {
        $proPluginVersion = PluginCommonConfig::getProPluginVersion();

        $freePluginVersion = PluginCommonConfig::getFreePluginVersion();

        if ($proPluginVersion > $freePluginVersion) {
            return PluginCommonConfig::getFreePluginBaseName();
        }

        return PluginCommonConfig::getProPluginBaseName();
    }

    private function checkAndUpdateProPluginInCache($pluginBaseName, $updatePlugins)
    {
        $updatePlugins = $this->normalizeUpdatePluginsCache($updatePlugins);

        $proPluginBaseName = PluginCommonConfig::getProPluginBaseName();

        if ($pluginBaseName === $proPluginBaseName && !isset($updatePlugins->response[$pluginBaseName])) {
            $updatedPluginCache = (new ProPluginUpdater())->checkCacheData($updatePlugins);
            set_site_transient('update_plugins', $updatedPluginCache);

            return $this->normalizeUpdatePluginsCache(get_site_transient('update_plugins'));
        }

        return $updatePlugins;
    }

    private function normalizeUpdatePluginsCache($updatePlugins)
    {
        if (!\is_object($updatePlugins)) {
            $updatePlugins = new stdClass();
        }

        if (!isset($updatePlugins->response) || !\is_array($updatePlugins->response)) {
            $updatePlugins->response = [];
        }

        if (!isset($updatePlugins->no_update) || !\is_array($updatePlugins->no_update)) {
            $updatePlugins->no_update = [];
        }

        if (!isset($updatePlugins->checked) || !\is_array($updatePlugins->checked)) {
            $updatePlugins->checked = [];
        }

        return $updatePlugins;
    }
}
