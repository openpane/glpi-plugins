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
 * Per-entity branding (login background, login logo, menu logos).
 */
class PluginBrandingConfig
{
    private const TABLE = 'glpi_plugin_branding_entities';

    public static function getTable(): string
    {
        return self::TABLE;
    }

    public static function install(): bool
    {
        global $DB;

        if (!$DB->tableExists(self::TABLE)) {
            $DB->doQuery(
                "CREATE TABLE `" . self::TABLE . "` (
                  `id` int unsigned NOT NULL AUTO_INCREMENT,
                  `entities_id` int unsigned NOT NULL,
                  `login_background` varchar(255) DEFAULT NULL,
                  `login_logo` varchar(255) DEFAULT NULL,
                  `logo_expanded` varchar(255) DEFAULT NULL,
                  `logo_collapsed` varchar(255) DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`entities_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;"
            );
        }

        if ($DB->tableExists(self::TABLE) && !$DB->fieldExists(self::TABLE, 'login_logo')) {
            $DB->doQuery(
                'ALTER TABLE `' . self::TABLE . '` ADD `login_logo` varchar(255) DEFAULT NULL AFTER `login_background`'
            );
        }

        self::ensureStorageDir();

        return true;
    }

    public static function uninstall(): bool
    {
        global $DB;

        if ($DB->tableExists(self::TABLE)) {
            $DB->doQuery('DROP TABLE `' . self::TABLE . '`');
        }

        $dir = self::getStorageBaseDir();
        if (is_dir($dir)) {
            foreach (glob($dir . '/*') ?: [] as $sub) {
                if (is_dir($sub)) {
                    array_map('unlink', glob($sub . '/*') ?: []);
                    @rmdir($sub);
                }
            }
            @rmdir($dir);
        }

        return true;
    }

    public static function getStorageBaseDir(): string
    {
        return GLPI_PLUGIN_DOC_DIR . '/branding';
    }

    public static function ensureStorageDir(): void
    {
        if (!is_dir(self::getStorageBaseDir())) {
            @mkdir(self::getStorageBaseDir(), 0755, true);
        }
    }

    /**
     * @return array{login_background: ?string, login_logo: ?string, logo_expanded: ?string, logo_collapsed: ?string}
     */
    public static function getRowForEntity(int $entities_id): array
    {
        global $DB;

        if ($entities_id < 0) {
            return self::emptyRow();
        }

        $it = $DB->request([
            'FROM'  => self::TABLE,
            'WHERE' => ['entities_id' => $entities_id],
            'LIMIT' => 1,
        ]);
        foreach ($it as $row) {
            return [
                'login_background' => $row['login_background'] ?: null,
                'login_logo'       => !empty($row['login_logo']) ? $row['login_logo'] : null,
                'logo_expanded'    => $row['logo_expanded'] ?: null,
                'logo_collapsed'   => $row['logo_collapsed'] ?: null,
            ];
        }

        return self::emptyRow();
    }

    /**
     * @return array{login_background: ?string, login_logo: ?string, logo_expanded: ?string, logo_collapsed: ?string}
     */
    private static function emptyRow(): array
    {
        return [
            'login_background' => null,
            'login_logo'       => null,
            'logo_expanded'    => null,
            'logo_collapsed'   => null,
        ];
    }

    /**
     * Resolve file walking entity ancestors (closest wins).
     *
     * @param 'login_background'|'login_logo'|'logo_expanded'|'logo_collapsed' $field
     *
     * @return null|array{entities_id: int, filename: string}
     */
    public static function resolveFileForEntity(int $entities_id, string $field): ?array
    {
        if (!in_array($field, ['login_background', 'login_logo', 'logo_expanded', 'logo_collapsed'], true)) {
            return null;
        }

        $chain = array_merge([$entities_id], getAncestorsOf('glpi_entities', $entities_id));
        foreach ($chain as $eid) {
            $eid = (int) $eid;
            $row = self::getRowForEntity($eid);
            if (!empty($row[$field])) {
                return ['entities_id' => $eid, 'filename' => $row[$field]];
            }
        }

        return null;
    }

    public static function getImagePath(int $entities_id, string $filename): string
    {
        return self::getStorageBaseDir() . '/' . $entities_id . '/' . $filename;
    }

    /**
     * @param 'login_background'|'login_logo'|'logo_expanded'|'logo_collapsed' $field
     */
    public static function handleUpload(int $entities_id, string $field, array $fileinfo): bool
    {
        if (
            empty($fileinfo['tmp_name'])
            || ($fileinfo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK
            || !is_uploaded_file($fileinfo['tmp_name'])
        ) {
            return false;
        }

        if (!in_array($field, ['login_background', 'login_logo', 'logo_expanded', 'logo_collapsed'], true)) {
            return false;
        }

        $path = $fileinfo['tmp_name'];
        if (!Document::isImage($path)) {
            Session::addMessageAfterRedirect(__('The file must be an image.'), false, ERROR);
            return false;
        }

        $ext = pathinfo($fileinfo['name'], PATHINFO_EXTENSION);
        $ext = preg_replace('/[^a-z0-9]/i', '', (string) $ext) ?: 'bin';
        $basename = $field . '_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);

        self::ensureStorageDir();
        $entity_dir = self::getStorageBaseDir() . '/' . $entities_id;
        if (!is_dir($entity_dir)) {
            @mkdir($entity_dir, 0755, true);
        }

        $dest = $entity_dir . '/' . $basename;
        if (!move_uploaded_file($path, $dest)) {
            Session::addMessageAfterRedirect(__('Could not save uploaded file.'), false, ERROR);
            return false;
        }

        self::deleteStoredFile($entities_id, $field);
        self::upsertField($entities_id, $field, $basename);

        return true;
    }

    /**
     * @param 'login_background'|'login_logo'|'logo_expanded'|'logo_collapsed' $field
     */
    public static function deleteStoredFile(int $entities_id, string $field): void
    {
        $row = self::getRowForEntity($entities_id);
        $old = $row[$field] ?? null;
        if (!empty($old)) {
            $full = self::getImagePath($entities_id, $old);
            if (is_file($full)) {
                @unlink($full);
            }
        }
    }

    /**
     * @param 'login_background'|'login_logo'|'logo_expanded'|'logo_collapsed' $field
     */
    public static function upsertField(int $entities_id, string $field, ?string $value): void
    {
        global $DB;

        $it = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::TABLE,
            'WHERE'  => ['entities_id' => $entities_id],
            'LIMIT'  => 1,
        ]);
        $id = null;
        foreach ($it as $r) {
            $id = (int) $r['id'];
        }

        if ($id !== null) {
            $DB->update(
                self::TABLE,
                [$field => $value],
                ['id' => $id]
            );
        } elseif ($value !== null && $value !== '') {
            $DB->insert(
                self::TABLE,
                [
                    'entities_id' => $entities_id,
                    $field        => $value,
                ]
            );
        }
    }

    public static function removeField(int $entities_id, string $field): void
    {
        if (!in_array($field, ['login_background', 'login_logo', 'logo_expanded', 'logo_collapsed'], true)) {
            return;
        }
        self::deleteStoredFile($entities_id, $field);

        global $DB;
        $it = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::TABLE,
            'WHERE'  => ['entities_id' => $entities_id],
            'LIMIT'  => 1,
        ]);
        foreach ($it as $r) {
            $DB->update(self::TABLE, [$field => null], ['id' => (int) $r['id']]);
            break;
        }
    }

    public static function processEntityFormPost(Entity $entity): void
    {
        $eid = (int) $entity->getID();
        if ($eid < 0) {
            return;
        }

        foreach (['login_background', 'login_logo', 'logo_expanded', 'logo_collapsed'] as $field) {
            $key = 'plugin_branding_' . $field;
            if (!empty($_POST['_plugin_branding_remove_' . $field])) {
                self::removeField($eid, $field);
            }
            if (!empty($_FILES[$key]['name'])) {
                self::handleUpload($eid, $field, $_FILES[$key]);
            }
        }
    }
}
