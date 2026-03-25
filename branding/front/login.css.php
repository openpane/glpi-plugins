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

require_once(__DIR__ . '/../../../inc/includes.php');
require_once(__DIR__ . '/../inc/config.class.php');

global $CFG_GLPI;

header('Content-Type: text/css; charset=UTF-8');
header('Cache-Control: public, max-age=300');

$resolved_bg   = PluginBrandingConfig::resolveFileForEntity(0, 'login_background');
$resolved_logo = PluginBrandingConfig::resolveFileForEntity(0, 'login_logo');

if ($resolved_bg === null && $resolved_logo === null) {
    exit;
}

$root = $CFG_GLPI['root_doc'] . '/plugins/branding/front/image.php';

$css = '';

if ($resolved_bg !== null) {
    $url_bg = htmlescape($root . '?field=login_background');
    $css .= <<<CSS
body.welcome-anonymous {
    margin: 0;
    min-height: 100vh;
    background-color: #1e293b;
    background-image: url("{$url_bg}");
    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
    background-attachment: fixed;
}

body.welcome-anonymous .page-anonymous {
    min-height: 100vh;
    width: 100%;
    background: transparent;
}

body.welcome-anonymous .page-anonymous > .flex-fill {
    min-height: 100vh;
}

CSS;
} else {
    $css .= <<<CSS
body.welcome-anonymous .page-anonymous {
    min-height: 100vh;
    width: 100%;
}

CSS;
}

if ($resolved_logo !== null) {
    $url_logo = htmlescape($root . '?field=login_logo');
    $css .= <<<CSS
body.welcome-anonymous .page-anonymous .glpi-logo {
    display: inline-block;
    width: min(280px, 85vw);
    max-width: 100%;
    height: 120px;
    background: url("{$url_logo}") no-repeat center center !important;
    background-size: contain !important;
    content: none !important;
    -webkit-mask: none !important;
    mask: none !important;
}

CSS;
}

/* Frosted card whenever this stylesheet is loaded (login background and/or custom logo) */
$css .= <<<CSS
body.welcome-anonymous .main-content-card {
    background-color: rgba(255, 255, 255, 0.72) !important;
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1px solid rgba(255, 255, 255, 0.45) !important;
    box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15);
}

body.welcome-anonymous .main-content-card > .card-header,
body.welcome-anonymous .main-content-card > .card-body {
    background-color: transparent !important;
}

CSS;

echo $css;
