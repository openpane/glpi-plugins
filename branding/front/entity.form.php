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

Session::checkRight(Config::$rightname, UPDATE);

$entities_id = (int) ($_POST['entities_id'] ?? 0);

$entity = new Entity();
if (!$entity->getFromDB($entities_id)) {
    Session::addMessageAfterRedirect(__('Invalid item'), false, ERROR);
    Html::back();
}

if (!Session::haveAccessToEntity($entities_id)) {
    Session::addMessageAfterRedirect(__('You do not have access to this entity.'), false, ERROR);
    Html::back();
}

if (empty($_POST['update'])) {
    Html::redirect($entity->getFormURLWithID($entities_id) . '&_glpi_tab=' . rawurlencode('PluginBrandingEntity$1'));
}

// CSRF is already validated by GLPI's CheckCsrfListener before this script runs; do not call validateCSRF again
// (the token is single-use and would fail on a second check).

PluginBrandingConfig::processEntityFormPost($entity);

Session::addMessageAfterRedirect(__('Branding configuration saved.'), false, INFO);

Html::redirect($entity->getFormURLWithID($entities_id) . '&_glpi_tab=' . rawurlencode('PluginBrandingEntity$1'));
