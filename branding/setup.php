<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 */

function plugin_version_branding(): array
{
    return [
        'name'         => 'Branding',
        'version'      => '1.1.0',
        'author'       => 'GLPI Community',
        'license'      => 'GPL v3+',
        'homepage'     => 'https://glpi-project.org',
        'requirements' => [
            'glpi' => [
                'min' => '10.0.0',
            ],
            'php'  => [
                'min' => '8.1',
            ],
        ],
    ];
}

function plugin_branding_check_config(): bool
{
    return true;
}

function plugin_branding_check_prerequisites(): bool
{
    return version_compare(PHP_VERSION, '8.1.0', '>=');
}

function plugin_branding_install(): bool
{
    require_once __DIR__ . '/inc/config.class.php';

    return PluginBrandingConfig::install();
}

function plugin_branding_uninstall(): bool
{
    require_once __DIR__ . '/inc/config.class.php';

    return PluginBrandingConfig::uninstall();
}

function plugin_init_branding(): void
{
    global $PLUGIN_HOOKS, $CFG_GLPI;

    $plugin = new Plugin();
    if (!$plugin->isActivated('branding')) {
        return;
    }

    $base = $CFG_GLPI['root_doc'];

    require_once __DIR__ . '/inc/entity.class.php';
    Plugin::registerClass(PluginBrandingEntity::class, [
        'addtabon' => [Entity::class],
    ]);

    $PLUGIN_HOOKS[\Glpi\Plugin\Hooks::ADD_HEADER_TAG_ANONYMOUS_PAGE]['branding'] = [
        [
            'tag'        => 'link',
            'properties' => [
                'rel'  => 'stylesheet',
                'type' => 'text/css',
                'href' => $base . '/plugins/branding/front/login.css.php',
            ],
        ],
    ];

    $PLUGIN_HOOKS[\Glpi\Plugin\Hooks::ADD_HEADER_TAG]['branding'] = [
        [
            'tag'        => 'link',
            'properties' => [
                'rel'  => 'stylesheet',
                'type' => 'text/css',
                'href' => $base . '/plugins/branding/front/menu.css.php',
            ],
        ],
    ];
}

function plugin_branding_boot(): void
{
    \Glpi\Http\Firewall::addPluginStrategyForLegacyScripts(
        'branding',
        '#^/front/(login\.css\.php|image\.php)$#',
        \Glpi\Http\Firewall::STRATEGY_NO_CHECK
    );
}
