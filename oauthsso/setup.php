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

function plugin_version_oauthsso(): array
{
    return [
        'name'           => 'OAuth SSO',
        'version'        => '1.0.0',
        'author'         => 'GLPI Community',
        'license'        => 'GPL v2+',
        'homepage'       => 'https://github.com/glpi-project/glpi',
        'requirements'   => [
            'glpi' => [
                'min' => '10.0.0',
            ],
            'php' => [
                'min' => '8.1',
            ],
        ],
    ];
}

function plugin_oauthsso_check_config(): bool
{
    return true;
}

function plugin_oauthsso_check_prerequisites(): bool
{
    if (version_compare(PHP_VERSION, '8.1.0', '<')) {
        return false;
    }
    return true;
}

function plugin_oauthsso_install(): bool
{
    require_once(__DIR__ . '/inc/config.class.php');
    $config = new PluginOauthssoConfig();
    return $config->install();
}

function plugin_oauthsso_uninstall(): bool
{
    require_once(__DIR__ . '/inc/config.class.php');
    $config = new PluginOauthssoConfig();
    return $config->uninstall();
}

function plugin_init_oauthsso(): void
{
    global $PLUGIN_HOOKS;

    $plugin = new Plugin();
    if (!$plugin->isActivated('oauthsso')) {
        return;
    }

    $PLUGIN_HOOKS[\Glpi\Plugin\Hooks::DISPLAY_LOGIN]['oauthsso'] = 'plugin_oauthsso_display_login';
    $PLUGIN_HOOKS[\Glpi\Plugin\Hooks::CONFIG_PAGE]['oauthsso']   = 'front/config.form.php';
    $PLUGIN_HOOKS[\Glpi\Plugin\Hooks::SECURED_CONFIGS]['oauthsso'] = [
        'oauthsso' => [
            'client_secret',
            'client_secret_microsoft',
            'client_secret_google',
        ],
    ];

    $PLUGIN_HOOKS['menu_toadd']['oauthsso'] = [
        'config' => 'PluginOauthssoConfig',
    ];
}

function plugin_oauthsso_boot(): void
{
    \Glpi\Http\Firewall::addPluginStrategyForLegacyScripts(
        'oauthsso',
        '#^/front/login\.php#',
        \Glpi\Http\Firewall::STRATEGY_NO_CHECK
    );
    \Glpi\Http\Firewall::addPluginStrategyForLegacyScripts(
        'oauthsso',
        '#^/front/callback\.php#',
        \Glpi\Http\Firewall::STRATEGY_NO_CHECK
    );
    // Callback não é stateless para permitir persistência da sessão após login
    // (alterar core seria necessário para stateless só na 1ª req. como smtp_oauth2_callback)
}

function plugin_oauthsso_display_login(): void
{
    global $CFG_GLPI;

    $providers = PluginOauthssoConfig::getEnabledProviders();
    if (count($providers) === 0) {
        return;
    }

    $base_url = $CFG_GLPI['root_doc'] . '/plugins/oauthsso/front/login.php';
    $redirect = $_GET['redirect'] ?? '';

    echo '<div id="oauthsso-login-block" class="mt-3">';

    if (count($providers) === 1) {
        $p = $providers[0];
        $url = $base_url . '?provider=' . rawurlencode($p['id']);
        if ($redirect !== '') {
            $url .= '&redirect=' . rawurlencode($redirect);
        }
        $label = sprintf(__('Login with %s'), $p['name']);
        $icon = $p['id'] === 'microsoft' ? 'ti ti-brand-microsoft' : 'ti ti-brand-google';
        echo '<div class="text-center">'
            . '<a href="' . htmlescape($url) . '" class="btn btn-outline-secondary w-100">'
            . '<i class="' . htmlescape($icon) . '"></i> '
            . htmlescape($label) . '</a>'
            . '</div>';
    } else {
        echo '<label class="form-label">' . htmlescape(__('Login with')) . '</label>';
        echo '<select name="oauthsso_provider" id="oauthsso_provider" class="form-select">';
        echo '<option value="">' . htmlescape(__('Choose provider...')) . '</option>';
        foreach ($providers as $p) {
            $url = $base_url . '?provider=' . rawurlencode($p['id']);
            if ($redirect !== '') {
                $url .= '&redirect=' . rawurlencode($redirect);
            }
            echo '<option value="' . htmlescape($url) . '">' . htmlescape($p['name']) . '</option>';
        }
        echo '</select>';
        echo '<script>
            document.getElementById("oauthsso_provider").addEventListener("change", function() {
                if (this.value) location.href = this.value;
            });
        </script>';
    }

    echo '</div>';
    echo '<script>
        (function() {
            function moveOAuthBlock() {
                var block = document.getElementById("oauthsso-login-block");
                var footer = document.querySelector("form .form-footer");
                if (block && footer) {
                    footer.parentNode.insertBefore(block, footer.nextSibling);
                }
            }
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", moveOAuthBlock);
            } else {
                moveOAuthBlock();
            }
        })();
    </script>';
}
