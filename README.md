## About Artwork

![Artwork Logo](https://artwork.software/wp-content/uploads/2023/05/artwork-logo.svg)

Artwork is a project organization tool that allows you to schedule projects with multiple events, tasks, and responsibilities. It helps you keep track of all the essential components of your projects. The project can be run using Laravel Sail. A light-weight command-line interface for interacting with Laravel's default Docker development environment.
Consult the [official documentation](https://laravel.com/docs/10.x/sail) for more information. 

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
composer install
```

2. Now start the Docker container by running:

```shell
./vendor/bin/sail up
```

The images will start building. It is recommended to replace the ./vendor/bin/sail command with a shell alias. 
Consultant the [documentation](https://laravel.com/docs/10.x/sail#configuring-a-shell-alias) to achieve that.
We will use the alias `sail` for the following commands.

3. Once the images are created you may have to open a new terminal window and install the frontend dependencies with a secret project key by running:

```shell
sail npm install
```

```shell
sail artisan key:generate
```

4. To migrate the database with fresh data, use the following command:

```shell
sail artisan migrate:fresh --seed
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

The site should be running now under http://artwork-laravel.test 🚀

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
