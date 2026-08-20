# artwork

![artwork Logo](https://artwork.software/wp-content/uploads/2023/05/artwork-logo.svg)

artwork is open-source operations software for theatres, festivals and other cultural venues. It covers the day-to-day running of a house in one system: event and room scheduling, shift and duty planning, staff availability, inventory and material issue, contacts, and project budgets. Instead of generic project boards, artwork models the things a venue actually works with — productions, events, rooms, crews, shifts and equipment — and keeps planning, staffing and resources connected in one place.

## Who this is for

artwork is built for organisations that run events as their core business:

- Theatres and production houses
- Festivals
- Concert halls
- Museums and exhibition venues with an event programme

Modules can be enabled individually, so a small venue can start with scheduling and rooms only, while a large house can run the full stack from shift planning to inventory and budgets.

## What artwork is not

- **Not a ticketing or box-office system** — artwork plans and runs events, it does not sell tickets.
- **Not financial accounting** — project budgets live in artwork, your bookkeeping stays in your accounting software.
- **Not payroll** — shift planning and hour tracking are covered, wage processing is not.
- **Not collection management** — despite the name, artwork does not manage artworks or museum collections. The inventory module manages operational equipment such as technical gear and materials.

## Background

artwork has been developed together with [Deichtorhallen Hamburg](https://www.deichtorhallen.de/), [Kampnagel](https://kampnagel.de/) and [HAU Hebbel am Ufer](https://www.hebbel-am-ufer.de/), funded by the [Kulturstiftung des Bundes](https://www.kulturstiftung-des-bundes.de/) (German Federal Cultural Foundation). It has been published under the AGPL-3.0 license since 2023.

- Website: [artwork.software](https://artwork.software)
- Documentation: [Wiki](https://github.com/artwork-software/artwork/wiki)

## Running artwork

artwork is self-hostable — you can install, operate and update it entirely on your own infrastructure using the instructions below. If you prefer support, [Caldero Systems](https://caldero-systems.de/) is available as a service partner for onboarding, hosting and support.

----------------

# Update from 1.5 to 1.6

- Make sure you ran the latest database migrations via ``php artisan migrate`` before updating. In this release we consolidated the migration to a new base dump. Since some migrations were moving and/or transforming data make sure you are up to date with the latest migrations from 1.5.1


# Update from 1.4 to 1.5

- Soketi has been removed as websocket service. We switched to Laravel Reverb. For an example configuration please take a look at dockerfiles/artwork-php.84.vhost.conf
- We added wkhtmltopdf as pdf rendering engine. The binaries must be available on the server under `/usr/bin/wkhtmltopdf` please take a look at https://wkhtmltopdf.org/ for more information and retrieve the binaries

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
   It should look like `APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=` please do **NOT** use the key from the example.
   
   If you receive an error regarding missing files wait a few minutes for the application to load the missing dependencies and set everything up. This may take up to 10 minutes depending on your setup.
4. Restart to load the new key:
   ```bash
   docker compose up -d
   ```
5. The frontend should be available at http://localhost


## Standalone

Artwork supports standalone installation on any Linux server.
Since we do not know your specific setup we cannot provide a sophisticated installation guide for any linux distribution.

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

## OpenAPI Specification

The specification of the machine API (`/api/v1`) is generated from the routes themselves, not
maintained by hand, and is therefore not committed to the repository. Generate it whenever you
need it — for example to import the API into Postman or to generate a client:

```bash
php artisan artwork:export-openapi
```

This writes `openapi.yaml` into the project root. Run it where the application environment works
(inside the app container, for example) — route analysis touches the database. The `servers` entry
in the generated document is `APP_URL` plus `/api/v1`, so the spec points at the instance you
generated it on.

----------------

If you have questions, feel free to open an issue.
