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

require_once(__DIR__ . '/../../../inc/includes.php');

global $CFG_GLPI;

Session::checkRight('config', UPDATE);

$config = new PluginOauthssoConfig();
$current = PluginOauthssoConfig::getConfig();

if (isset($_POST['update'])) {
    $providers_by_id = [];
    foreach ($current['providers'] ?? [] as $p) {
        $providers_by_id[$p['id'] ?? ''] = $p;
    }

    $providers = [];
    $provider_types = PluginOauthssoConfig::getProviderTypes();

    foreach (array_keys($provider_types) as $provider_id) {
        $enabled = !empty($_POST['provider_' . $provider_id . '_enabled']);
        $client_id = trim($_POST['provider_' . $provider_id . '_client_id'] ?? '');
        $client_secret = $_POST['provider_' . $provider_id . '_client_secret'] ?? '';
        $tenant = $provider_id === 'microsoft' ? trim($_POST['provider_microsoft_tenant'] ?? 'common') : '';

        $providers[] = [
            'id'        => $provider_id,
            'type'      => $provider_id === 'microsoft' ? 'azure' : 'google',
            'name'      => $provider_types[$provider_id]['name'],
            'client_id' => $client_id,
            'tenant'    => $tenant,
            'enabled'   => $enabled,
        ];

        if ($client_secret !== '') {
            Config::setConfigurationValues(PluginOauthssoConfig::getConfigContext(), [
                'client_secret_' . $provider_id => $client_secret,
            ]);
        } else {
            $existing = $providers_by_id[$provider_id] ?? [];
            if (!empty($existing['client_secret'])) {
                Config::setConfigurationValues(PluginOauthssoConfig::getConfigContext(), [
                    'client_secret_' . $provider_id => $existing['client_secret'],
                ]);
            }
        }
    }

    Config::setConfigurationValues(PluginOauthssoConfig::getConfigContext(), [
        'profiles_id'  => (int) ($_POST['profiles_id'] ?? 0),
        'entities_id'  => (int) ($_POST['entities_id'] ?? 0),
        'providers'    => json_encode($providers),
    ]);

    Session::addMessageAfterRedirect(__('Configuration saved.'));
    Html::back();
}

Html::header(
    PluginOauthssoConfig::getTypeName(),
    '',
    'config',
    'PluginOauthssoConfig'
);

$provider_types = PluginOauthssoConfig::getProviderTypes();
$providers_by_id = [];
foreach ($current['providers'] ?? [] as $p) {
    $providers_by_id[$p['id'] ?? ''] = $p;
}

echo '<div class="center">';
echo '<form method="post" action="" data-submit-once>';
echo '<table class="tab_cadre_fixehov">';
echo '<tr class="tab_bg_1"><th colspan="2">' . __('OAuth SSO - Providers') . '</th></tr>';

foreach ($provider_types as $provider_id => $info) {
    $p = $providers_by_id[$provider_id] ?? [];
    $enabled = $p['enabled'] ?? false;
    $client_id = $p['client_id'] ?? '';
    $client_secret = $p['client_secret'] ?? '';
    $tenant = $p['tenant'] ?? 'common';

    echo '<tr class="tab_bg_2">';
    echo '<td colspan="2"><strong><i class="' . htmlescape($info['icon']) . '"></i> ' . htmlescape($info['name']) . '</strong></td>';
    echo '</tr>';

    echo '<tr class="tab_bg_2">';
    echo '<td>' . __('Enabled') . '</td>';
    echo '<td><input type="checkbox" name="provider_' . htmlescape($provider_id) . '_enabled" value="1" ' . ($enabled ? 'checked' : '') . ' /></td>';
    echo '</tr>';

    echo '<tr class="tab_bg_2">';
    echo '<td>' . __('Client ID') . '</td>';
    echo '<td><input type="text" name="provider_' . htmlescape($provider_id) . '_client_id" value="' . htmlescape($client_id) . '" class="form-control" size="60" /></td>';
    echo '</tr>';

    echo '<tr class="tab_bg_2">';
    echo '<td>' . __('Client Secret') . '</td>';
    echo '<td><input type="password" name="provider_' . htmlescape($provider_id) . '_client_secret" value="" class="form-control" size="60" autocomplete="new-password" placeholder="' . __('Leave empty to keep current') . '" /></td>';
    echo '</tr>';

    if ($provider_id === 'microsoft') {
        echo '<tr class="tab_bg_2">';
        echo '<td>' . _x('oauth', 'Tenant ID') . '</td>';
        echo '<td><input type="text" name="provider_microsoft_tenant" value="' . htmlescape($tenant) . '" class="form-control" size="40" placeholder="common" />';
        echo '<br><small class="form-text">' . __('Use "common" for multi-tenant applications.') . '</small></td>';
        echo '</tr>';
    }
}

echo '<tr class="tab_bg_1"><th colspan="2">' . __('Default for new users') . '</th></tr>';

echo '<tr class="tab_bg_2">';
echo '<td>' . __('Default profile for new users') . '</td>';
echo '<td>';
Profile::dropdown([
    'name'  => 'profiles_id',
    'value' => $current['profiles_id'] ?? 0,
]);
echo '</td>';
echo '</tr>';

echo '<tr class="tab_bg_2">';
echo '<td>' . __('Default entity for new users') . '</td>';
echo '<td>';
Entity::dropdown([
    'name'  => 'entities_id',
    'value' => $current['entities_id'] ?? 0,
]);
echo '</td>';
echo '</tr>';

echo '<tr class="tab_bg_2">';
echo '<td colspan="2" class="center">';
echo '<input type="submit" name="update" class="btn btn-primary" value="' . _x('button', 'Save') . '" />';
echo '</td>';
echo '</tr>';

echo '</table>';
echo Html::closeForm();

echo '<p class="mt-4"><strong>' . __('Redirect URI to configure in OAuth provider') . ':</strong></p>';
echo '<code>' . htmlescape($CFG_GLPI['url_base'] . '/plugins/oauthsso/front/callback.php') . '</code>';

echo '<p class="mt-4"><strong>' . __('Important') . ':</strong></p>';
echo '<p>' . __('Users are created automatically on first OAuth login. Login must match userPrincipalName (Microsoft) or email (Google).') . '</p>';

echo '</div>';

Html::footer();
