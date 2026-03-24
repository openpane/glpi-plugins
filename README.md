# OAuth SSO Plugin for GLPI

Plugin enabling SSO authentication with **Microsoft** and/or **Google** using OAuth 2.0 / OpenID Connect.

## Requirements

- GLPI 10.0.0 or higher
- PHP 8.1 or higher
- PHP extensions: OpenSSL, JSON (included in PHP)
- Composer libraries already bundled in GLPI: `thenetworg/oauth2-azure`, `league/oauth2-google`

## Installation

1. Copy the `oauthsso` folder to the GLPI plugins directory (`plugins/oauthsso` or `marketplace/oauthsso`)
2. Go to **Setup** > **Plugins**
3. Install and activate the "OAuth SSO" plugin

## Configuration

### Microsoft (Azure AD / Entra ID)

1. Go to [Azure Portal](https://portal.azure.com) > **Microsoft Entra ID** > **App registrations** > **New registration**
2. Redirect URI: **Web** → `https://your-glpi/plugins/oauthsso/front/callback.php`
3. Under **API permissions**, add: Microsoft Graph > Delegated: `openid`, `email`, `profile`

### Google

1. Go to [Google Cloud Console](https://console.cloud.google.com) > **APIs & Services** > **Credentials**
2. Create **OAuth 2.0 Client ID** credentials (type: Web application)
3. Under **Authorized redirect URIs**, add: `https://your-glpi/plugins/oauthsso/front/callback.php`

### In GLPI

1. Go to **Setup** > **OAuth SSO**
2. For each provider (Microsoft, Google): enable and fill in **Client ID** and **Client Secret**
3. For Microsoft: configure **Tenant ID** (`common` for multi-tenant)
4. Set **Default profile** and **Default entity** for users created automatically

## Login screen

- **Single provider configured**: direct button "Login with Microsoft" or "Login with Google"
- **Multiple providers**: "Login with" dropdown to select the provider

## User creation

Users are **created automatically** on first login via OAuth SSO:
- **Microsoft**: login = `userPrincipalName` or `preferred_username`
- **Google**: login = `email`
- First and last name from provider claims
- Profile and entity from plugin configuration

## File structure

```
plugins/oauthsso/
├── setup.php
├── README.md
├── inc/
│   └── config.class.php
└── front/
    ├── config.form.php
    ├── login.php      (initiates OAuth)
    └── callback.php   (handles Azure/Google response)
```

## License

GPL v2+
