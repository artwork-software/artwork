## About Artwork

![Artwork Logo](https://artwork.software/wp-content/uploads/2023/05/artwork-logo.svg)

Artwork is a project organization tool for scheduling projects with events, tasks, and responsibilities. It helps teams keep track of all essential project components.

# Installation

Artwork can be installed standalone on a dedicated server or as a multi-container app via Docker, we recommend to use the Docker-approach.

## Docker

> **This Docker setup is for demo purposes only. To get a productive-ready installation you need to fill in your credentials according to your server circumstances in the .env-file especially take care to have the emailing-service setup correctly and that the settings fit to your firewall settings of your setup. When doing this it can be needed, that you also adjust the dockerfiles to your setup.**

### Setup

1. Copy ``.env.example`` to ``.env`` and adjust the values
2. Build and start:
   ```bash
   docker compose build artwork
   docker compose up -d
   ```
3. Generate an app key:
   ```bash
   docker compose exec artwork php artisan key:generate --show
   ```
   Copy the output into the ``APP_KEY`` variable in your ``.env`` file.
4. Restart to load the new key:
   ```bash
   docker compose up -d
   ```


## Standalone

Artwork supports standalone installation on any Linux server.
Since we do not know your specific setup we cannot provide a sofisticated installation guide for any linux distribution.

### Requirements

- PHP 8.4 with extensions:
  cli, fpm, mysql, gd, imagick, curl, imap, mbstring, xml, zip, bcmath, soap, intl, readline, ldap, redis, swoole, igbinary, msgpack, memcached, pcov
- wkhtmltopdf 0.12.6 (patched Qt)
- MariaDB 11
- Redis
- Node.js 22+
- Meilisearch 1.22

### Additional Requirements 

- Artwork requires Websockets to be accessible. We use Laravel Reverb as host. Which need to be accessible from the system and frontend-
- Run composer install and npm install after every update
- Rebuild the the frontend via ``npm run build`` after every update
- You should have a minutely cronjob running to run the ``php artisan schedule:run`` command.
- You need to make sure the queue is running via ``php artisan queue:work``
- After every update remember to run database migrations and the ``php artisan artwork:update`` command.

Example configurations for the nginx, redis and php services can be found in the ``dockerfiles`` folder.

### Setup

1. Copy ``.env.example`` to ``.env`` and adjust the values to match your environment
2. Install dependencies:
   ```bash
   php composer.phar install
   npm install
   ```
3. Generate an app key:
   ```bash
   php artisan key:generate --show
   ```
   Copy the output into the ``APP_KEY`` variable in your ``.env`` file.
4. Set ``APP_URL`` in ``.env`` to your domain (including ``http://`` or ``https://``)
5. Run the setup command:
   ```bash
   php artisan artwork:update
   ```

### E-Mail

Artwork relies on emails for many features like account verification and password resets.
To get emails working, fill in the following block in your ``.env`` file with your mail server settings:

```
MAIL_HOST=
MAIL_PORT=
MAIL_MAILER=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
```

### SSL

We do not ship certificates. Configure SSL as you would for any nginx installation:
https://nginx.org/en/docs/http/configuring_https_servers.html


Dependencies (Composer & NPM) are installed automatically on container start.

### Test Credentials

If you seed the database with dummy data, you can log in with:

| Account | E-Mail | Password |
|---------|--------|----------|
| Admin (all permissions) | anna.musterfrau@artwork.software | TestPass1234!$ |
| User (limited permissions) | lisa.musterfrau@artwork.software | TestPass1234!$ |

----------------

# Branch Structure

- **``main``** — Stable/production branch
- **``staging``** — Pre-release testing (Beta)
- **``dev``** — Development and feature integration

----------------

# API

## Setup

1. Log in to Artwork
2. Navigate to **Tool Settings → Interfaces**
3. Click "Create API Key"
4. Enter a name and optionally set an expiration date
5. Copy the generated key and store it securely

----------------

If you have questions, feel free to open an issue.
