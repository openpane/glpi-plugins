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

/**
 * "Branding" tab on Entity forms.
 */
class PluginBrandingEntity extends CommonGLPI
{
    public static $rightname = 'config';

    public static function getTypeName($nb = 0)
    {
        return _n('Branding', 'Brandings', $nb);
    }

    public static function getIcon()
    {
        return 'ti ti-photo';
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$item instanceof Entity || $withtemplate) {
            return '';
        }

        $id = (int) $item->getID();
        if ($id < 0 || !$item->can($id, READ)) {
            return '';
        }

        if (!Session::haveRight(Config::$rightname, UPDATE)) {
            return '';
        }

        return self::createTabEntry(self::getTypeName(1), 0, $item::class, self::getIcon());
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof Entity || $withtemplate) {
            return false;
        }

        $id = (int) $item->getID();
        if ($id < 0 || !$item->can($id, READ) || !Session::haveRight(Config::$rightname, UPDATE)) {
            return false;
        }

        if (!Session::haveAccessToEntity($id)) {
            echo '<div class="alert alert-warning">' . htmlescape(__('You do not have access to this entity.')) . '</div>';
            return true;
        }

        require_once __DIR__ . '/config.class.php';

        global $CFG_GLPI;

        $row      = PluginBrandingConfig::getRowForEntity($id);
        $canedit  = Session::haveAccessToEntity($id);
        $form_url = $CFG_GLPI['root_doc'] . '/plugins/branding/front/entity.form.php';

        echo '<div class="card"><div class="card-body">';

        echo '<p class="text-muted">' . htmlescape(
            __('Login page images (background and logo) use the root entity (ID 0) and inherited values. Menu logos use the active entity and inherit from parent entities.')
        ) . '</p>';

        if ($canedit) {
            echo '<form method="post" action="' . htmlescape($form_url) . '" enctype="multipart/form-data" data-submit-once>';
            echo '<input type="hidden" name="_glpi_csrf_token" value="' . htmlescape(Session::getNewCSRFToken()) . '" />';
            echo '<input type="hidden" name="entities_id" value="' . (int) $id . '" />';
        }

        $fields = [
            'login_background' => __('Login page background'),
            'login_logo'       => __('Login page logo'),
            'logo_expanded'    => __('Main menu logo (expanded)'),
            'logo_collapsed'   => __('Main menu logo (collapsed)'),
        ];

        foreach ($fields as $field => $label) {
            $fname = 'plugin_branding_' . $field;
            $has   = !empty($row[$field]);

            echo '<div class="mb-3 row"><label class="col-form-label col-sm-4">'
                . htmlescape($label) . '</label><div class="col-sm-8">';

            if ($has) {
                echo '<div class="mb-2"><span class="badge text-bg-secondary">'
                    . htmlescape(__('File configured'))
                    . '</span></div>';
                if ($canedit) {
                    echo '<div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="_plugin_branding_remove_'
                        . htmlescape($field) . '" value="1" id="rm_' . htmlescape($field) . '" />'
                        . '<label class="form-check-label" for="rm_' . htmlescape($field) . '">'
                        . htmlescape(__('Remove')) . '</label></div>';
                }
            }

            if ($canedit) {
                echo '<input type="file" class="form-control" name="' . htmlescape($fname)
                    . '" accept="image/*" /><small class="text-muted d-block mt-1">'
                    . htmlescape(__('Recommended: PNG or JPEG. Large files may be slow to load.'))
                    . '</small>';
            }

            echo '</div></div>';
        }

        if ($canedit) {
            echo '<div class="text-center">';
            echo '<button type="submit" name="update" class="btn btn-primary" value="1">'
                . htmlescape(_sx('button', 'Save'))
                . '</button>';
            echo '</div>';
            echo '</form>';
        }

        echo '</div></div>';

        return true;
    }
}
