# Branding

GLPI plugin for **per-entity visual branding**: login background, login logo, and main menu logos (expanded and collapsed).

> If you use the [GLPI plugins collection](../README.md) repository layout, copy **only** the `branding` folder into GLPI’s `plugins/` or `marketplace/` directory.

## Requirements

- GLPI 10.0.0 or higher
- PHP 8.1 or higher
- Writable `files/_plugins` (GLPI file storage for plugin uploads)

## Installation

1. Copy the `branding` directory to `plugins/branding` (or `marketplace/branding`) on your GLPI server.
2. In GLPI: **Setup** → **Plugins** → install and enable **Branding**.

### Upgrade from 1.0.x

After upgrading plugin files, open **Setup** → **Plugins** and run **Upgrade** on Branding (or disable and enable the plugin) so the database schema is updated (e.g. `login_logo` column).

## Configuration

1. **Administration** → **Entities** → select an entity → tab **Branding**.
2. Upload or remove images, then **Save**.

Inheritance follows GLPI entities: settings can be defined on the root entity (ID `0`) and inherited where configured.

## Usage

| Setting | Effect |
|---------|--------|
| **Login page background** | Full-page image behind the authentication screen. |
| **Login page logo** | Image above the login form, replacing the default GLPI mark. |
| **Main menu logo (expanded)** | Replaces the default GLPI logo when the sidebar is expanded. |
| **Main menu logo (collapsed)** | Replaces the compact logo when the menu is collapsed. |

Uploaded files are stored under:

```text
files/_plugins/branding/{entities_id}/
```

## Layout

```
branding/
├── setup.php
├── hook.php
├── README.md
├── inc/
│   ├── config.class.php
│   └── entity.class.php
└── front/
    ├── entity.form.php
    ├── image.php
    ├── login.css.php
    └── menu.css.php
```

## License

GPL v3+
