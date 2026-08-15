<?php

namespace BitApps\Utils;

// Prevent direct script access
if (!defined('ABSPATH')) {
    exit;
}

use BitApps\Utils\Services\LicenseService;
use stdClass;

/**
 * Helps to update plugin.
 */
final class ProPluginUpdater
{
    public const PLUGIN_AUTHOR_HOMEPAGE_URL = 'https://bitapps.pro';

    public const PLUGIN_AUTHOR = 'Bit Apps';

    private $name;

    private $slug;

    private $version;

    private $label;

    private $freeVersion;

    private $cacheKey;

    public function __construct()
    {
        $this->slug = PluginCommonConfig::getProPluginSlug();

        $this->name = $this->slug . '/' . $this->slug . '.php';

        $this->version = PluginCommonConfig::getProPluginVersion();

        $this->freeVersion = PluginCommonConfig::getFreePluginVersion();

        $this->label = PluginCommonConfig::getFreePluginTitle() . ' Connect Wordpress Plugins And External Applications';

        $this->cacheKey = md5($this->slug . '_plugin_info');

        $this->registerHooks();

        $this->removeCache();

        add_action('admin_notices', [$this, 'licenseExpirationNotice']);
    }

    public function licenseExpirationNotice()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $pageNow;

        if ($pageNow !== 'plugins.php') {
            return;
        }

        $licenseData = LicenseService::getLicenseData();

        if (!empty($licenseData['expireIn'])) {
            $expireInDays = (strtotime($licenseData['expireIn']) - time()) / DAY_IN_SECONDS;

            $allowedTags = [
                'div' => [
                    'class' => [],
                ],
                'p' => [],
            ];

            if ($expireInDays < 25) {
                $notice = $expireInDays > 0
                ? \sprintf('%s License will expire in %s days', (int) $expireInDays, PluginCommonConfig::getFreePluginTitle())
                : \sprintf('%s License is expired', PluginCommonConfig::getFreePluginTitle());

                // phpcs:ignore
                echo wp_kses(
                    "<div class='notice notice-error is-dismissible'>
                <p>{$notice}</p>
            </div>",
                    $allowedTags
                );
            }
        }
    }

    public function checkUpdate($cacheData)
    {
        global $pageNow;

        if (!\is_object($cacheData)) {
            $cacheData = new stdClass();
        }

        if ($pageNow === 'plugins.php' && is_multisite()) {
            return $cacheData;
        }

        return $this->checkCacheData($cacheData);
    }

    public function setProPluginInfo($data, $action = '', $args = null)
    {
        if ($action !== 'plugin_information') {
            return $data;
        }
        if (!isset($args->slug) || ($args->slug !== $this->slug)) {
            return $data;
        }

        $cacheKey = $this->slug . '_api_request_' . md5(serialize($this->slug));

        $apiResponseCache = get_site_transient($cacheKey);

        if (empty($apiResponseCache)) {
            $apiResponse = LicenseService::getUpdatedInfo();

            $formattedApiResponse = $this->formatApiResponse($apiResponse);
            set_site_transient($cacheKey, $formattedApiResponse, DAY_IN_SECONDS);

            return $formattedApiResponse;
        }

        return $apiResponseCache;
    }

    public function showUpdateInfo($file)
    {
        if ($this->name !== $file) {
            return;
        }

        if (is_network_admin()) {
            return;
        }

        if (!is_multisite()) {
            return;
        }

        if (!current_user_can('update_plugins')) {
            return;
        }

        remove_filter('pre_set_site_transient_update_plugins', [$this, 'checkUpdate']);

        $updateCache = get_site_transient('update_plugins');

        $updateCache = $this->checkCacheData($updateCache);

        set_site_transient('update_plugins', $updateCache);

        add_filter('pre_set_site_transient_update_plugins', [$this, 'checkUpdate']);
    }

    public function removeCache()
    {
        global $pageNow;

        if ($pageNow === 'update-core.php' && isset($_GET['force-check'])) {
            delete_option(PluginCommonConfig::getProPluginPrefix() . $this->cacheKey);
        }
    }

    public function checkCacheData($cacheData)
    {
        if (!\is_object($cacheData)) {
            $cacheData = new stdClass();
        }

        if (empty($cacheData->checked)) {
            return $cacheData;
        }

        $versionInfo = $this->getCache();

        if (\is_null($versionInfo) || $versionInfo === false) {
            $versionInfo = LicenseService::getUpdatedInfo();
            if (is_wp_error($versionInfo)) {
                $versionInfo = new stdClass();
                $versionInfo->error = true;
            }
            $this->setCache($versionInfo);
        }

        if (!empty($versionInfo->error)) {
            return $cacheData;
        }

        // include an unmodified $wp_version
        include ABSPATH . WPINC . '/version.php';

        if (version_compare($wp_version, $versionInfo->requireWP, '<')) {
            return $cacheData;
        }

        $requiredFreeVersion = $versionInfo->requiresFree ?? null;

        if (
            !empty($requiredFreeVersion)
            && version_compare($this->freeVersion, $requiredFreeVersion, '<')
        ) {
            return $cacheData;
        }

        if (version_compare($this->version, $versionInfo->version, '<')) {
            $cacheData->response[$this->name] = $this->formatApiResponse($versionInfo);
        } else {
            $noUpdateInfo = (object) [
                'id'          => $this->name,
                'slug'        => $this->slug,
                'plugin'      => $this->name,
                'new_version' => $this->version,
                'url'         => '',
                'package'     => '',
                'banners'     => [
                    'high' => 'https://ps.w.org/bit-flow/assets/banner-772x250.jpg?rev=2657199',
                ],
                'banners_rtl'   => [],
                'tested'        => '',
                'requires_php'  => '',
                'compatibility' => new stdClass(),
            ];
            $cacheData->no_update[$this->name] = $noUpdateInfo;
        }

        $cacheData->last_checked = current_time('timestamp');
        $cacheData->checked[$this->name] = $this->version;

        return $cacheData;
    }

    private function registerHooks()
    {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'checkUpdate']);

        add_action('delete_site_transient_update_plugins', [$this, 'removeCache']);

        add_filter('plugins_api', [$this, 'setProPluginInfo'], 10, 3);

        remove_action('after_plugin_row_' . $this->name, 'wp_plugin_update_row');

        add_action('after_plugin_row_' . $this->name, [$this, 'showUpdateInfo'], 10, 1);
    }

    private function getCache()
    {
        $cacheData = get_option(PluginCommonConfig::getProPluginPrefix() . $this->cacheKey);

        if (empty($cacheData['timeout']) || current_time('timestamp') > $cacheData['timeout']) {
            return false;
        }

        return $cacheData['value'];
    }

    private function setCache($cacheValue)
    {
        $expiration = strtotime('+12 hours', current_time('timestamp'));

        $data = [
            'timeout' => $expiration,
            'value'   => $cacheValue,
        ];

        update_option(PluginCommonConfig::getProPluginPrefix() . $this->cacheKey, $data, 'no');
    }

    private function formatApiResponse($apiResponse)
    {
        $formattedData = new stdClass();

        $formattedData->name = $this->label;

        $formattedData->slug = $this->slug;

        $formattedData->plugin = $this->name;

        $formattedData->id = $this->name;

        $formattedData->author = self::PLUGIN_AUTHOR;

        $formattedData->homepage = self::PLUGIN_AUTHOR_HOMEPAGE_URL;
        if (is_wp_error($apiResponse)) {
            $formattedData->requires = '';

            $formattedData->requires_php = '';

            $formattedData->tested = '';

            $formattedData->new_version = $this->version;

            $formattedData->last_updated = '';

            $formattedData->download_link = '';

            $formattedData->banners = [
                'high' => 'https://ps.w.org/bit-flow/assets/banner-772x250.jpg?rev=2657199',
            ];

            $formattedData->sections = null;

            return $formattedData;
        }
        $formattedData->requires = $apiResponse->requireWP;

        // WordPress only blocks the update (and auto-update) on incompatible
        // PHP when requires_php is present in the update payload.
        $formattedData->requires_php = $apiResponse->requirePHP ?? '';

        $formattedData->tested = $apiResponse->tested;

        $formattedData->new_version = $apiResponse->version;

        $formattedData->last_updated = $apiResponse->updatedAt;

        $formattedData->download_link = !empty($apiResponse->downloadLink) ? $apiResponse->downloadLink . '/' . $this->slug . '.zip' : '';

        $formattedData->package = !empty($apiResponse->downloadLink) ? $apiResponse->downloadLink . '/' . $this->slug . '.zip' : '';

        $formattedData->banners = [
            'high' => 'https://ps.w.org/bit-flow/assets/banner-772x250.jpg?rev=2657199',
        ];
        $formattedData->sections = $apiResponse->sections;

        return $formattedData;
    }
}
