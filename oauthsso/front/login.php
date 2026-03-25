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

$_SESSION["glpicookietest"] = 'testcookie';

$config = new PluginOauthssoConfig();
if (!$config->isConfigured()) {
    Session::addMessageAfterRedirect(__('OAuth SSO is not configured. Please contact your administrator.'), false, ERROR);
    Html::redirect($CFG_GLPI['root_doc'] . '/index.php');
}

$provider_id = $_GET['provider'] ?? '';
$providers = PluginOauthssoConfig::getEnabledProviders();
$provider_config = null;
foreach ($providers as $p) {
    if (($p['id'] ?? '') === $provider_id) {
        $provider_config = $p;
        break;
    }
}

if ($provider_config === null) {
    Session::addMessageAfterRedirect(__('Invalid or disabled OAuth provider.'), false, ERROR);
    Html::redirect($CFG_GLPI['root_doc'] . '/index.php');
}

$redirect_uri = $CFG_GLPI['url_base'] . '/plugins/oauthsso/front/callback.php';
$redirect_param = $_GET['redirect'] ?? '';
if ($redirect_param !== '') {
    $_SESSION['oauthsso_redirect'] = $redirect_param;
}
$_SESSION['oauthsso_provider'] = $provider_id;

try {
    $client_secret = $provider_config['client_secret'] ?? '';

    if ($provider_id === PluginOauthssoConfig::PROVIDER_MICROSOFT) {
        $provider = new \TheNetworg\OAuth2\Client\Provider\Azure([
            'clientId'     => $provider_config['client_id'],
            'clientSecret' => $client_secret,
            'redirectUri'  => $redirect_uri,
            'defaultEndPointVersion' => \TheNetworg\OAuth2\Client\Provider\Azure::ENDPOINT_VERSION_2_0,
            'scopes'       => ['openid', 'email', 'profile', 'https://graph.microsoft.com/User.Read'],
        ]);
        if (!empty($provider_config['tenant'])) {
            $provider->tenant = $provider_config['tenant'];
        }
    } elseif ($provider_id === PluginOauthssoConfig::PROVIDER_GOOGLE) {
        $provider = new \League\OAuth2\Client\Provider\Google([
            'clientId'     => $provider_config['client_id'],
            'clientSecret' => $client_secret,
            'redirectUri'  => $redirect_uri,
        ]);
    } else {
        throw new RuntimeException(sprintf(__('Unknown provider: %s'), $provider_id));
    }

    $auth_params = [];
    if ($provider_id === PluginOauthssoConfig::PROVIDER_GOOGLE) {
        $auth_params = ['scope' => ['openid', 'email', 'profile']];
    }
    $auth_url = $provider->getAuthorizationUrl($auth_params);
    $_SESSION['oauthsso_state'] = $provider->getState();
    header('Location: ' . $auth_url);
    exit;
} catch (Throwable $e) {
    global $PHPLOGGER;
    $PHPLOGGER->error('OAuth SSO login error: ' . $e->getMessage(), ['exception' => $e]);
    Session::addMessageAfterRedirect(
        htmlescape(sprintf(__('OAuth SSO error: %s'), $e->getMessage())),
        false,
        ERROR
    );
    Html::redirect($CFG_GLPI['root_doc'] . '/index.php');
}
