## About Artwork

![Artwork Logo](https://artwork.software/wp-content/uploads/2023/05/artwork-logo.svg)

Artwork is a project organization tool that allows you to schedule projects with multiple events, tasks, and responsibilities. It helps you keep track of all the essential components of your projects.

## Installation

To install and run this project, please follow the steps below:

1. Clone the repository to your local machine:

```shell
git clone <repository_url>
```

2. Build the Docker containers by running the following command in your terminal:

```shell
docker-compose build --no-cache
```

3. Once the build is complete, start the containers:

```shell
docker-compose up -d
```

4. Update the `.env` file and change the MeiliSearch IP address accordingly:

```shell
MEILISEARCH_HOST=http://artwork_tools-meilisearch-1:7700
```

Database Connection:

```shell    
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=CONTAINER_NAME OR ID
```

5. To migrate the database with fresh data, use the following command:

```shell
docker-compose run --rm artisan migrate:fresh --seed
```
7. To run npm dev, use the following command:
```shell
docker-compose run --rm npm run dev 
```

8. Finally, visit the local development environment by opening your browser and navigating to [http://localhost](http://localhost).

----------------

If the page appears blank, please follow the error handling steps below:
Run npm run dev to start the development version of the page:
```shell
   docker-compose run --rm npm run dev
```
If the issue persists, execute npm run build to compile the project and generate the production-ready version:
```shell
docker-compose run --rm npm run build
```

----------------


To run various commands in the project, you can use the following instructions:

- To run `npm` commands, use the following command:

```shell
docker-compose run --rm npm <command>
```

For example, to install dependencies, you can run:

```shell
docker-compose run --rm npm install
```

- To run `composer` commands, use the following command:

```shell
docker-compose run --rm composer <command>
```

For example, to update dependencies, you can run:

```shell
docker-compose run --rm composer update
```

- To run `artisan` commands, use the following command:

```shell
docker-compose run --rm artisan <command>
```

For example, to generate a new migration file, you can run:

```shell
docker-compose run --rm artisan make:migration create_users_table
```

Feel free to use these commands to interact with the project and execute the necessary tasks efficiently.

----------------

Feel free to explore the features of Artwork and manage your projects effectively!
