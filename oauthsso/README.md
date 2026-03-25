# OAuth SSO

GLPI plugin for SSO with **Microsoft** (Azure AD / Entra ID) and/or **Google** using OAuth 2.0 / OpenID Connect.

> If this directory came from the multi-plugin repository, copy **only** the `oauthsso` folder into GLPI’s `plugins/` or `marketplace/` directory.

## Requirements

- GLPI 10.0.0 or higher
- PHP 8.1 or higher
- PHP extensions: OpenSSL, JSON (typically bundled with PHP)
- OAuth client libraries shipped with GLPI: `thenetworg/oauth2-azure`, `league/oauth2-google`

## Installation

1. Copy the `oauthsso` directory to `plugins/oauthsso` (or `marketplace/oauthsso`) on your GLPI server.
2. In GLPI: **Setup** → **Plugins** → install and enable **OAuth SSO**.

## Configuration

### Microsoft (Azure AD / Entra ID)

1. In [Azure Portal](https://portal.azure.com): **Microsoft Entra ID** → **App registrations** → **New registration**.
2. **Redirect URI** (Web): `https://your-glpi.example.org/plugins/oauthsso/front/callback.php` (adjust base URL and path if GLPI is not at the site root).
3. **API permissions** (Microsoft Graph, delegated): `openid`, `email`, `profile`, and **User.Read** (used when `givenName` / `surname` are not present in the ID token).

### Google

1. In [Google Cloud Console](https://console.cloud.google.com): **APIs & Services** → **Credentials**.
2. Create an **OAuth 2.0 Client ID** (application type: Web application).
3. **Authorized redirect URIs**: `https://your-glpi.example.org/plugins/oauthsso/front/callback.php`.

### In GLPI

1. **Setup** → **OAuth SSO**.
2. For each provider: enable, then set **Client ID** and **Client Secret**.
3. For Microsoft: set **Tenant ID** (`common` for multi-tenant apps).
4. Set **Default profile** and **Default entity** for users created on first login.

## Usage

### Login screen

- **One provider configured:** a direct “Login with Microsoft” or “Login with Google” button.
- **Several providers:** a “Login with” control to choose the provider.

### User provisioning

On first successful OAuth login, GLPI users are created when needed:

| Provider   | Login field                         | Name                           |
|-----------|--------------------------------------|--------------------------------|
| Microsoft | `userPrincipalName` or `preferred_username` | From token / Graph claims |
| Google    | `email`                              | From ID token claims           |

Profile and entity come from the plugin configuration above.

## Layout

```
oauthsso/
├── setup.php
├── README.md
├── inc/
│   └── config.class.php
└── front/
    ├── config.form.php
    ├── login.php      # starts OAuth
    └── callback.php   # provider callback
```

## License

GPL v2+
