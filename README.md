## About Artwork

![Artwork Logo](https://artwork.software/wp-content/uploads/2023/05/artwork-logo.svg)

Artwork is a project organization tool that allows you to schedule projects with multiple events, tasks, and responsibilities. It helps you keep track of all the essential components of your projects. The project can be run using Laravel Sail. A light-weight command-line interface for interacting with Laravel's default Docker development environment.
Consult the [official documentation](https://laravel.com/docs/10.x/sail) for more information.

# Maintenance

For artwork-instances that are already in usage, we will add commands here in the readme, that need to be used to add new permissions or components to the existing db. Dont worry, these commands cant harm your db, they only fill in things if they arent in the db already.

Command to add new permissions for newly added Modules:

 ``php artisan artwork:update-permissions``

Command to add new components to the project-tab-library:

``artwork:add-new-components``

Try these after major updates to be sure, that you are not missing new features.

# Frequent Setup Bugs

If you have problems migrating after doing the newest upgrade and get this migration to fail:

2024_11_23_165534_add_show_qualifications_to_user

Then it is a compatibility issue between MySql and MariaDB, for the ongoing development we decided to use MariaDB, so you will need to swap to MariaDB. To help with the switch we built a script in the .install folder to do this for you. Important: Do a backup of your db before using this script, then run the script .install/db-install.sh, after that the migration will work.

# Installation

Artwork supports to be installed as a standalone application for dedicated servers or as a multi container app powered by docker

## Standalone

### Prerequisites

**Currently only Ubuntu is supported!**

Either `root` account or a user with `sudo` rights. The installation is fully automated and without prompts.

### Installation

Login to your server and run ``sudo curl -fsSL https://raw.githubusercontent.com/artwork-software/artwork/main/ubuntu-install.sh | sh``

Alternatively copy the ``ubuntu-install.sh`` script to your server. No other files, like the rest of the repository, or software installations needed.

Simple give the script executable permissions ``chmod +x ubuntu-install.sh``

and run it ``./ubuntu-install.sh`` 

What it will do:
- Install nginx as webserver and setups up a default config. **This will override the default config. Create a backup if you have other services running**
- Install mysql-8 and create a user account for the application and fills the database
- Install NodeJs in version 18.x (LTS) 
- Create a service for the queue worker
- Setup a cronjob for the planned schedules
- Install Soketi (global) as Pusher compatible service and daemonizes it
- Setup and install PHP with all needed plugins
- Install meilisearch
- Installs Artwork itself

**It is highly discouraged to run the installer multiple times as some steps are intended to be executed once.**

### After installation

Edit the ``.env`` file located in `/var/www/html/.env`

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

Soketi, the websocket service, also needs to be configured. See the official documentation https://docs.soketi.app/getting-started/ssl-configuration on how to achieve this.

## Docker installation Standalone

Artwork offers a stand alone containerized version of the application. This is useful if you want to run the application on a dedicated server or in a cloud environment.

### Prerequisites

Clone the repository `git clone git@github.com:artwork-software/artwork.git`.
You need [Docker](https://www.docker.com/) and the .env of the repository. It is advised to use the `.env.standalone.example` file and rename it to `.env`

Everytime the container is built it will perform an auto update on database and components. To update Artwork itself please update the Github repository.


### Installation

To boot the container you can simply run the following command:

`docker compose -f docker-compose-production.yml up -d`

The application needs an app key variable set. For this please run the command ``docker compose -f docker-compose-production.yml exec artwork php artisan key:generate --show``

This will output a key. Copy this key and paste it into the .env file under the APP_KEY variable.

Afterwards reload the container to load the new ``.env`` by running `docker compose -f docker-compose-production.yml up -d` again.

Feel free to modify the .env file to your needs, e.g. with E-Mail credentials.

### Updates

You can modify the `$ARTWORK_VERSION` variable in the .env file. By default it is set to `main` which is the latest stable version of Artwork.
The always pull policy ensures, that it will automatically update to the latest version on the next restart. It will also automatically migrate the database if necessary.

## Docker installation (Laravel Sail)

### Prerequisites

Laravel Sail is supported on macOS, Linux, and Windows (via [WSL2](https://learn.microsoft.com/en-us/windows/wsl/about)).

[Docker](https://www.docker.com/) and [composer](https://getcomposer.org/) have to be installed to run the project.

### Introduction

Laravel Sail will create 4 Docker images. 
- The PHP project, 
- a [MySQL](https://www.mysql.com/de/) instance (database), 
- a [meilisearch](https://www.meilisearch.com/) instance (to enable fuzzy search)
- a [mailpit](https://github.com/axllent/mailpit) instance (to preview emails sent by the application)

It is recommended to not have any services running on the ports 80 and 3306.

### Installation

1. Clone the repository to your local machine:

```shell
git clone https://github.com/artwork-software/artwork.git
```
2. Access the project in the terminal and copy the .env.example file and rename it to .env

```shell
composer install --ignore-platform-reqs
```

2. Now start the Docker container by running:

```shell
./vendor/bin/sail up
```

The images will start building. It is recommended to replace the ./vendor/bin/sail command with a shell alias. 
Consult the [documentation](https://laravel.com/docs/10.x/sail#configuring-a-shell-alias) to achieve that.
We will use the alias `sail` for the following commands.

3. Once the images are created you may have to open a new terminal window and install the frontend dependencies with a secret project key by running:

```shell
sail npm install
```

```shell
sail artisan key:generate
```

4. To migrate the database with dummy data, use the following command:

```shell
sail artisan migrate:fresh --seed
```

To Delete your current database use this command:
```shell
sail artisan migrate:fresh
```

If you want to set up the database fresh for production without dummy data, use this command to fill the database with the necessary tables:

```shell
sail artisan db:seed:production
```
5. Start the queue using:

```shell
sail artisan queue:work
```

6. Start the frontend by running
   
```shell
sail npm run dev 
```
7. Publish the app storage folder to display the artwork logo by running
   
```shell
sail artisan storage:link
```

The site should be running now under http://localhost 🚀

You can also visit your:
- Mails under http://localhost:8025
- Meilisearch under http://localhost:7700/

To connect to your application's MySQL database from your local machine, you may use a graphical database management application such as TablePlus. By default, the MySQL database is accessible at localhost port 3306 and the access credentials correspond to the values of your DB_USERNAME and DB_PASSWORD environment variables. Or, you may connect as the root user, which also utilizes the value of your DB_PASSWORD environment variable as its password.

----------------

If you have problems installing the project or find any other bugs please open a issue [here](https://github.com/artwork-software/artwork/issues).

----------------


To run various commands in the project, you can use the following instructions:

- To run `npm` commands, use the following command:

```shell
sail npm <command>
```

- To see all your changes to the code directly you can also run this command besides the ones from above:

```shell
sail npm run hot
```

For example, to install dependencies, you can run:

```shell
sail npm install
```

- To run `artisan` commands, use the following command:

```shell
sail artisan <command>
```

For example, to generate a new migration file, you can run:

```shell
sail artisan make:migration create_users_table
```

Feel free to use these commands to interact with the project and execute the necessary tasks efficiently.

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
