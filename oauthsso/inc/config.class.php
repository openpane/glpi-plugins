<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

class PluginOauthssoConfig extends CommonGLPI
{
    public static $rightname = 'config';

    private const CONFIG_CONTEXT = 'plugin:oauthsso';

    public const PROVIDER_MICROSOFT = 'microsoft';
    public const PROVIDER_GOOGLE = 'google';

    public static function getConfigContext(): string
    {
        return self::CONFIG_CONTEXT;
    }

    public static function getTypeName($nb = 0)
    {
        return __('OAuth SSO');
    }

    public static function getIcon()
    {
        return 'ti ti-key';
    }

    public static function getMenuContent(): array
    {
        global $CFG_GLPI;

        if (!Config::canUpdate()) {
            return [];
        }
        return [
            'title' => self::getTypeName(),
            'page'  => $CFG_GLPI['root_doc'] . self::getConfigPageUrl(),
            'icon'  => self::getIcon(),
        ];
    }

    public static function getConfigPageUrl(): string
    {
        return '/plugins/oauthsso/front/config.form.php';
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        return false;
    }

    /**
     * @return array{profiles_id: int, entities_id: int, providers: array}
     */
    public static function getConfig(): array
    {
        $all = Config::getConfigurationValues(self::CONFIG_CONTEXT, [
            'profiles_id',
            'entities_id',
            'providers',
            'client_id',
            'client_secret',
            'tenant',
            'client_secret_microsoft',
            'client_secret_google',
        ]);

        $providers = [];
        if (!empty($all['providers'])) {
            $decoded = json_decode($all['providers'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $p) {
                    $id = $p['id'] ?? '';
                    $p['client_secret'] = $all['client_secret_' . $id] ?? '';
                    $providers[] = $p;
                }
            }
        } elseif (!empty($all['client_id']) && !empty($all['client_secret'])) {
            $providers = [[
                'id'         => self::PROVIDER_MICROSOFT,
                'type'       => 'azure',
                'name'       => __('Microsoft'),
                'client_id'  => $all['client_id'],
                'client_secret' => $all['client_secret'],
                'tenant'    => $all['tenant'] ?? 'common',
                'enabled'   => true,
            ]];
        }

        return [
            'profiles_id'   => (int) ($all['profiles_id'] ?? 0),
            'entities_id'   => (int) ($all['entities_id'] ?? 0),
            'providers'     => $providers,
        ];
    }

    /**
     * @return array List of enabled, configured providers
     */
    public static function getEnabledProviders(): array
    {
        $config = self::getConfig();
        $providers = [];
        foreach ($config['providers'] ?? [] as $p) {
            if (!empty($p['enabled']) && !empty($p['client_id'])) {
                $secret = $p['client_secret'] ?? '';
                if (!empty($secret)) {
                    $decrypted = @(new GLPIKey())->decrypt($secret);
                    if ($decrypted !== null && $decrypted !== '') {
                        $secret = $decrypted;
                    }
                }
                if (!empty($secret)) {
                    $p['client_secret'] = $secret;
                    $providers[] = $p;
                }
            }
        }
        return $providers;
    }

    public function isConfigured(): bool
    {
        return count(self::getEnabledProviders()) > 0;
    }

    public static function getProviderTypes(): array
    {
        return [
            self::PROVIDER_MICROSOFT => ['name' => __('Microsoft'), 'icon' => 'ti ti-brand-microsoft'],
            self::PROVIDER_GOOGLE    => ['name' => __('Google'), 'icon' => 'ti ti-brand-google'],
        ];
    }

    public function install(): bool
    {
        return true;
    }

    public function uninstall(): bool
    {
        Config::deleteConfigurationValues(self::CONFIG_CONTEXT, [
            'profiles_id',
            'entities_id',
            'providers',
            'client_id',
            'client_secret',
            'tenant',
            'client_secret_microsoft',
            'client_secret_google',
        ]);
        return true;
    }
}
