<?php

namespace BitApps\Utils\HTTP\Controllers;

// Prevent direct script access
if (!\defined('ABSPATH')) {
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

        include_once ABSPATH . 'wp-includes/update.php';

        wp_update_plugins();

        $updatePlugins = get_site_transient('update_plugins');

        if (!\is_object($updatePlugins)) {
            $updatePlugins = new stdClass();
        }

        if (!isset($updatePlugins->response) || !\is_array($updatePlugins->response)) {
            $updatePlugins->response = [];
        }

        $pluginSlug = $this->getPluginSlug();

        $upgrader = (new Plugin_Upgrader(new Automatic_Upgrader_Skin()));

        $updatePlugins = $this->checkAndUpdateProPluginInCache($pluginSlug, $updatePlugins);

        if (isset($updatePlugins->response[$pluginSlug])) {
            $pluginUpgraded = $upgrader->upgrade($pluginSlug);

            if (is_wp_error($pluginUpgraded)) {
                return 'Error updating plugin: ' . $pluginUpgraded->get_error_message();
            }

            $pluginActivated = activate_plugin($pluginSlug);

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

        $freePluginSlug = PluginCommonConfig::getFreePluginSlug();

        $pluginSlug = $freePluginSlug . '/' . $freePluginSlug . '.php';

        $updatedPlugins = get_site_transient('update_plugins');

        if (\is_object($updatedPlugins) && isset($updatedPlugins->response[$pluginSlug])) {
            $latestVersion = $updatedPlugins->response[$pluginSlug]->new_version;
        }

        return wp_send_json_success(
            [
                'latest_version' => $latestVersion,
            ]
        );
    }

    private function getPluginSlug()
    {
        $freePluginSlug = PluginCommonConfig::getFreePluginSlug();

        $proPluginSlug = PluginCommonConfig::getProPluginSlug();

        $proPluginVersion = PluginCommonConfig::getProPluginVersion();

        $freePluginVersion = PluginCommonConfig::getFreePluginVersion();

        if ($proPluginVersion > $freePluginVersion) {
            $pluginSlug = $freePluginSlug . '/' . $freePluginSlug . '.php';
        } else {
            $pluginSlug = $proPluginSlug . '/' . $proPluginSlug . '.php';
        }

        return $pluginSlug;
    }

    private function checkAndUpdateProPluginInCache($pluginSlug, $updatePlugins)
    {
        if (!\is_object($updatePlugins)) {
            $updatePlugins = new stdClass();
        }

        if (!isset($updatePlugins->response) || !\is_array($updatePlugins->response)) {
            $updatePlugins->response = [];
        }

        $proPluginSlug = PluginCommonConfig::getProPluginSlug();
        $proPluginFile = $proPluginSlug . '/' . $proPluginSlug . '.php';

        if ($pluginSlug === $proPluginFile && !isset($updatePlugins->response[$pluginSlug])) {
            $updatedPluginCache = (new ProPluginUpdater())->checkCacheData($updatePlugins);
            set_site_transient('update_plugins', $updatedPluginCache);

            $updatePlugins = get_site_transient('update_plugins');

            if (!\is_object($updatePlugins)) {
                $updatePlugins = new stdClass();
            }

            if (!isset($updatePlugins->response) || !\is_array($updatePlugins->response)) {
                $updatePlugins->response = [];
            }

            return $updatePlugins;
        }

        return $updatePlugins;
    }
}
