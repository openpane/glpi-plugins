# GLPI Plugins

This repository is a **collection of independent GLPI plugins**. Each plugin lives in its own top-level directory (`oauthsso`, `branding`, …). They share no runtime dependency on each other unless explicitly documented in a plugin’s README.

Use this repo if you want the source for several plugins in one place. **Install only the folders you need** into your GLPI instance.

## Available plugins

| Directory | Plugin | Description |
|-----------|--------|-------------|
| [`oauthsso/`](oauthsso/README.md) | **OAuth SSO** | Sign-in with Microsoft and/or Google using OAuth 2.0 / OpenID Connect. |
| [`branding/`](branding/README.md) | **Branding** | Per-entity login background and logos, plus main menu logos. |

## How to install from this repository

GLPI expects each plugin under `plugins/<plugin_key>/` or `marketplace/<plugin_key>/`, where `<plugin_key>` matches the folder name (e.g. `oauthsso`, `branding`).

1. Copy **only** the plugin directory you need into your GLPI installation, for example:
   - `path/to/glpi/plugins/oauthsso/`
   - `path/to/glpi/plugins/branding/`
2. In GLPI, go to **Setup** → **Plugins**, then install and enable the plugin.
3. Follow the **Installation** and configuration sections in that plugin’s own README.

You can clone the whole repository and copy individual folders, or use a sparse checkout / submodule workflow if you prefer; GLPI does not require the repository root.

## Documentation

- **Repository (this file):** what the collection contains and how folders map to GLPI.
- **Each plugin:** its own `README.md` with requirements, installation, configuration, and usage.

## License

Each plugin declares its license in `setup.php` and in its README. Refer to the individual plugin documentation for the exact terms.
