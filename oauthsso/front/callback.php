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

/**
 * Resolve first name and surname from OAuth userinfo / ID token / Graph shapes.
 *
 * @param array<string, mixed> $user_data
 *
 * @return array{firstname: string, realname: string}
 */
function plugin_oauthsso_extract_profile_names(array $user_data): array
{
    $firstname = trim((string) (
        $user_data['given_name'] ?? $user_data['givenName'] ?? $user_data['firstname'] ?? ''
    ));
    $realname = trim((string) (
        $user_data['family_name'] ?? $user_data['surname'] ?? $user_data['last_name'] ?? $user_data['lastName'] ?? ''
    ));

    if ($firstname === '' && $realname === '') {
        $full = trim((string) (
            $user_data['name'] ?? $user_data['displayName'] ?? ''
        ));
        if ($full !== '') {
            $parts = preg_split('/\s+/u', $full, 2);
            $firstname = $parts[0] ?? '';
            $realname = $parts[1] ?? '';
        }
    }

    return [
        'firstname' => $firstname,
        'realname'  => $realname,
    ];
}

global $CFG_GLPI;

$_SESSION["glpicookietest"] = 'testcookie';

if (!array_key_exists('cookie_refresh', $_GET)) {
    $url = htmlescape(
        $_SERVER['REQUEST_URI']
        . (str_contains($_SERVER['REQUEST_URI'], '?') ? '&' : '?')
        . 'cookie_refresh'
    );
    echo <<<HTML
<html>
<head>
    <meta http-equiv="refresh" content="0;URL='{$url}'"/>
</head>
<body></body>
</html>
HTML;
    return;
}

$config = new PluginOauthssoConfig();
if (!$config->isConfigured()) {
    Session::addMessageAfterRedirect(__('OAuth SSO is not configured.'), false, ERROR);
    Html::redirect($CFG_GLPI['root_doc'] . '/index.php');
}

if (
    (array_key_exists('error', $_GET) && $_GET['error'] !== '')
    || (array_key_exists('error_description', $_GET) && $_GET['error_description'] !== '')
) {
    Session::addMessageAfterRedirect(
        htmlescape(sprintf(__('Authorization failed: %s'), $_GET['error_description'] ?? $_GET['error'] ?? '')),
        false,
        ERROR
    );
    Html::redirect($CFG_GLPI['root_doc'] . '/index.php');
}

if (
    !array_key_exists('state', $_GET)
    || !array_key_exists('oauthsso_state', $_SESSION)
    || $_GET['state'] !== $_SESSION['oauthsso_state']
) {
    Session::addMessageAfterRedirect(__('Unable to verify authorization. Please try again.'), false, ERROR);
    Html::redirect($CFG_GLPI['root_doc'] . '/index.php');
}

if (!array_key_exists('code', $_GET)) {
    Session::addMessageAfterRedirect(__('Authorization code not received.'), false, ERROR);
    Html::redirect($CFG_GLPI['root_doc'] . '/index.php');
}

$provider_id = $_SESSION['oauthsso_provider'] ?? '';
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

if ($provider_id === PluginOauthssoConfig::PROVIDER_MICROSOFT) {
    $provider = new \TheNetworg\OAuth2\Client\Provider\Azure([
        'clientId'     => $provider_config['client_id'],
        'clientSecret' => $provider_config['client_secret'],
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
        'clientSecret' => $provider_config['client_secret'],
        'redirectUri'  => $redirect_uri,
    ]);
} else {
    Session::addMessageAfterRedirect(__('Unknown OAuth provider.'), false, ERROR);
    Html::redirect($CFG_GLPI['root_doc'] . '/index.php');
}

try {
    $token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
    $resource_owner = $provider->getResourceOwner($token);
    $user_data = $resource_owner->toArray();

    if ($provider_id === PluginOauthssoConfig::PROVIDER_MICROSOFT && $provider instanceof \TheNetworg\OAuth2\Client\Provider\Azure) {
        try {
            $access_for_graph = $token;
            $graph_me = $provider->get('me', $access_for_graph);
            if (is_array($graph_me)) {
                if (!empty($graph_me['givenName'])) {
                    $user_data['given_name'] = $graph_me['givenName'];
                    $user_data['givenName'] = $graph_me['givenName'];
                }
                if (!empty($graph_me['surname'])) {
                    $user_data['family_name'] = $graph_me['surname'];
                    $user_data['surname'] = $graph_me['surname'];
                }
                if (!empty($graph_me['displayName'])) {
                    $user_data['displayName'] = $graph_me['displayName'];
                }
                if (!empty($graph_me['mail']) && empty($user_data['mail'])) {
                    $user_data['mail'] = $graph_me['mail'];
                }
            }
        } catch (Throwable $e) {
            global $PHPLOGGER;
            $PHPLOGGER->warning(
                'OAuth SSO: Microsoft Graph /me skipped: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
} catch (Throwable $e) {
    global $PHPLOGGER;
    $PHPLOGGER->error('OAuth SSO callback error: ' . $e->getMessage(), ['exception' => $e]);
    Session::addMessageAfterRedirect(
        __('Authentication failed. Please contact your administrator.'),
        false,
        ERROR
    );
    Html::redirect($CFG_GLPI['root_doc'] . '/index.php');
}

$login = null;
if ($provider_id === PluginOauthssoConfig::PROVIDER_MICROSOFT) {
    $login = $user_data['userPrincipalName'] ?? $user_data['preferred_username'] ?? $user_data['email'] ?? null;
} else {
    $login = $user_data['email'] ?? $user_data['id'] ?? null;
}

if (empty($login)) {
    Session::addMessageAfterRedirect(__('Could not retrieve user identity from provider.'), false, ERROR);
    Html::redirect($CFG_GLPI['root_doc'] . '/index.php');
}

$login = trim($login);

$oauth_config = PluginOauthssoConfig::getConfig();

$user = new User();
if (!$user->getFromDBbyName($login)) {
    $user = Session::callAsSystem(function () use ($login, $user_data, $oauth_config) {
        $new_user = new User();
        if ($new_user->getFromDBbyNameAndAuth($login, Auth::EXTERNAL, 0)) {
            return $new_user;
        }
        $profiles_id = (int) ($oauth_config['profiles_id'] ?? 0);
        $entities_id = (int) ($oauth_config['entities_id'] ?? 0);
        $email = $user_data['mail'] ?? $user_data['email'] ?? $user_data['userPrincipalName'] ?? $user_data['preferred_username'] ?? '';
        $names = plugin_oauthsso_extract_profile_names($user_data);
        $input = [
            'name'        => $login,
            'authtype'    => Auth::EXTERNAL,
            'auths_id'    => 0,
            '_extauth'    => true,
            'realname'    => $names['realname'],
            'firstname'   => $names['firstname'],
            'is_active'   => 1,
        ];
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $input['_useremails'] = ['-1' => $email];
        }
        if ($profiles_id > 0) {
            $input['_profiles_id'] = $profiles_id;
        }
        if ($entities_id >= 0) {
            $input['_entities_id'] = $entities_id;
            $input['entities_id'] = $entities_id;
        }
        if ($new_user->add($input)) {
            return $new_user;
        }
        return null;
    });
    if ($user === null) {
        Session::addMessageAfterRedirect(
            sprintf(
                __('User "%s" could not be created automatically. Please ask your administrator to create a user with login "%s" and auth type "External".'),
                $login,
                $login
            ),
            false,
            ERROR
        );
        Html::redirect($CFG_GLPI['root_doc'] . '/index.php');
    }
}

if (!$user->fields['is_active']) {
    Session::addMessageAfterRedirect(__('Your account is disabled.'), false, ERROR);
    Html::redirect($CFG_GLPI['root_doc'] . '/index.php');
}

$auth = new Auth();
$auth->auth_succeded = true;
$auth->user = $user;
$auth->extauth = 1;
Session::init($auth);

unset($_SESSION['oauthsso_state']);
unset($_SESSION['oauthsso_provider']);
$redirect = $_SESSION['oauthsso_redirect'] ?? '';
unset($_SESSION['oauthsso_redirect']);

Auth::redirectIfAuthenticated($redirect ?: null);
