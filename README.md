## About Artwork

![Artwork Logo](https://artwork.software/wp-content/uploads/2023/05/artwork-logo.svg)

Artwork is a project organization tool that allows you to schedule projects with multiple events, tasks, and responsibilities. It helps you keep track of all the essential components of your projects. The project can be run using Laravel Sail. A light-weight command-line interface for interacting with Laravel's default Docker development environment.
Consult the [official documentation](https://laravel.com/docs/10.x/sail) for more information.

# Update from v1.3.0 to v1.4.0

- Checkout the v1.3.0 version 
- Make sure you backup your database and run all database migrations ``php artisan migrate``
- Update all the dependencies by running ``composer update``

- ** Backup your database **
- Checkout the v1.4.0 version
- Run the following commands to update your database and components:
- ``composer install``
- ``php artisan migrate``
- ``php artisan artwork:update``

# Maintenance

For artwork-instances that are already in usage, we will add commands here in the readme, that need to be used to add new permissions or components to the existing db. Dont worry, these commands cant harm your db, they only fill in things if they arent in the db already.

Command to add new permissions for newly added Modules:

 ``php artisan artwork:update-permissions``

Command to add new components to the project-tab-library:

``artwork:add-new-components``

Command to add new feature-related data to the databases:

``php artisan artwork:update``

Try these after major updates to be sure, that you are not missing new features.

# Frequent Setup Bugs

If you have problems migrating after doing the newest upgrade and get this migration to fail:

2024_11_23_165534_add_show_qualifications_to_user

Then it is a compatibility issue between MySql and MariaDB, for the ongoing development we decided to use MariaDB, so you will need to swap to MariaDB. To help with the switch we built a script in the .install folder to do this for you. Important: Do a backup of your db before using this script, then run the script .install/db-install.sh, after that the migration will work.

# Installation

Artwork supports to be installed as a standalone application for dedicated servers or as a multi container app powered by docker

## Standalone

### Prerequisites

Make sure you install the following software

- php 8.4
- MariaDB 11
- Redis
- Node 22+
- Meilisearch 1.22

An example config for the services can be found in the ``dockerfiles`` folder

### Installation

Copy the `.env.example into` into a ``.env`` file and adjust the values for the services.

Once the environment is prepared run

- ``php composer.phar install``
- ``npm install``

The application needs an app key variable set. For this please run the command ``php artisan key:generate --show``
This will output a key. Copy this key and paste it into the .env file under the APP_KEY variable.

Then run ``php artisan artwork:container-update``

Locate the string ``APP_URL=http://localhost`` and replace `http://localhost` with your domain. `http` or `https` are required.

For e-mail support locate the following block in the same file and fill in your credentials
````
MAIL_HOST=
MAIL_PORT=
MAIL_MAILER=
MAIL_USERNAME=Inbox-Name
MAIL_PASSWORD=
MAIL_ENCRYPTION=
````

### SSL

We do not ship dummy or selfsigned certificates with the installation.

SSL should be configured like you would your regular nginx instance https://nginx.org/en/docs/http/configuring_https_servers.html


## Docker installation

*Please note that this docker setup is only for demo purposes*

### Installation

To boot the container with run the following command:

``docker compose build artwork`` to build the base image, then

`docker compose up -d` to boot the application

The application needs an app key variable set. For this please run the command 

``docker compose  exec artwork php artisan key:generate --show``

This will output a key. Copy this key and paste it into the .env file under the APP_KEY variable.

Afterwards reload the container to load the new ``.env`` by running `docker compose up -d --build`.

----------------

# Branch Structure

- **`dev` Branch**: This is where developers test their building blocks. It serves as the primary development branch for integrating new features and experiments.

- **`staging` Branch**: This branch acts as the test server environment and can be considered as the Beta version. It is used for pre-release testing to ensure stability before deployment to production.

- **`main` Branch**: This is our stable branch and should serve as the basis for all production systems. It contains the most reliable and tested version of our code.

----------------

# Test Instance

If you use the docker installation and filled the database with dummy data you can use the following credentials to login to the test instance:

For the admin account (with all permissions):
Mail: anna.musterfrau@artwork.software
Password: TestPass1234!$

For the user account (with limited permissions):
Mail: lisa.musterfrau@artwork.software
Password: TestPass1234!$

a full documentation of all features will be released and found here, when we have finished developement of version 1.0

To be able to invite new Users you need to update the .env file with your mail credentials and the APP_URL

If you have questions, feel free to open an issue :) 

Caldero Systems hosted Test Server:

Caldero Systems also hosts a Test Server which can be reached under https://artwork.caldero-systems.de/

On this server several people will use it, so dont use confidential data there. 

login data for this server is:

Mail: max.mustermann@artwork.software
Password:TestPass1234!$

For the user accounts (with limited permissions, fell free to edit the permissions of these 2 users when testing):
Mail: anna.musterfrau@artwork.software
Password: TestPass1234!$

Mail: lisa.musterfrau@artwork.software
Password: TestPass1234!$

NOTE: the Mailing of this test-instance will always end in a mailtrap which is setup by us. so there wont be outgoing mails to your emails.

Feel free to explore the features of Artwork and manage your projects effectively!

# API

## Prerequisites

Before you can use the Inventory API endpoint, you need to generate an API key:

1. Log in to Artwork
2. Navigate to **Tool Settings → Interfaces**
3. Click on "Create API Key"
4. Enter a name for your API key
5. Optional: Set an expiration date
6. Click on "Create"
7. Copy the generated API key and store it securely
