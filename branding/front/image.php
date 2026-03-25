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

use function Safe\readfile;

$field = $_GET['field'] ?? '';
if (!in_array($field, ['login_background', 'login_logo', 'logo_expanded', 'logo_collapsed'], true)) {
    http_response_code(404);
    exit;
}

if ($field === 'login_background' || $field === 'login_logo') {
    $resolved = PluginBrandingConfig::resolveFileForEntity(0, $field);
} else {
    Session::checkLoginUser();
    $resolved = PluginBrandingConfig::resolveFileForEntity((int) Session::getActiveEntity(), $field);
}

if ($resolved === null) {
    http_response_code(404);
    exit;
}

$path = PluginBrandingConfig::getImagePath($resolved['entities_id'], $resolved['filename']);
$real = @realpath($path);
$base = @realpath(PluginBrandingConfig::getStorageBaseDir());

if (
    $real === false
    || $base === false
    || !str_starts_with($real, $base)
    || !is_file($real)
    || !Document::isImage($real)
) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . Toolbox::getMime($real));
header('Cache-Control: private, max-age=3600');
readfile($real);
