## About Artwork

![Artwork Logo](https://artwork.software/wp-content/uploads/2023/05/artwork-logo.svg)

Artwork is a project organization tool that allows you to schedule projects with multiple events, tasks, and responsibilities. It helps you keep track of all the essential components of your projects. The project can be run using Laravel Sail. A light-weight command-line interface for interacting with Laravel's default Docker development environment.
Consult the [official documentation](https://laravel.com/docs/10.x/sail) for more information.

# Installation

Artwork supports to be installed as a standalone application for dedicated servers or as a multi container app powered by docker

# Standalone

## Prerequisites

**Currently only Ubuntu is supported!**

Either `root` account or a user with `sudo` rights. The installation is fully automated and without prompts.

## Installation

Copy the ``ubuntu-install.sh`` script to your server. No other files, like the rest of the repository, or software installations needed.

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

## After installation

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

# Docker installation

# Installation

Artwork supports to be installed as a standalone application for dedicated servers or as a multi container app powered by docker

# Standalone

## Prerequisites

**Currently only Ubuntu is supported!**

Either `root` account or a user with `sudo` rights. The installation is fully automated and without prompts.

## Installation

Login to your server and run ``sudo curl -fsSL https://raw.githubusercontent.com/artwork-software/artwork/dev/ubuntu-install.sh | sh``

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

## After installation

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

# Docker installation

## Prerequisites
Laravel Sail is supported on macOS, Linux, and Windows (via [WSL2](https://learn.microsoft.com/en-us/windows/wsl/about)).

[Docker](https://www.docker.com/) and [composer](https://getcomposer.org/) have to be installed to run the project.

## Introduction
Laravel Sail will create 4 Docker images. 
- The PHP project, 
- a [MySQL](https://www.mysql.com/de/) instance (database), 
- a [meilisearch](https://www.meilisearch.com/) instance (to enable fuzzy search)
- a [mailpit](https://github.com/axllent/mailpit) instance (to preview emails sent by the application)

It is recommended to not have any services running on the ports 80 and 3306.

## Installation

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

Feel free to explore the features of Artwork and manage your projects effectively!

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
