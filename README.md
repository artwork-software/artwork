
## About Artwork

Artwork is a project organisation tool. 
It enables the scheduling of projects with multiple events, tasks, and responsibilities. 

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

4. To migrate the database with fresh data, use the following command:

```shell
docker-compose run --rm artisan migrate:fresh --seed
```

5. If you encounter errors during the migration process with MeiliSearch, you can try the following steps:

- Run the following command to retrieve the IP address of the `artwork_tools-meilisearch-1` container:

```shell
docker inspect -f '{{range.NetworkSettings.Networks}}{{.IPAddress}}{{end}}' artwork_tools-meilisearch-1
```

- Update the `.env` file and change the MeiliSearch IP address accordingly.

6. To run `npm dev`, use the following command:

```shell
docker-compose run --rm npm run dev
```

7. Finally, visit the local development environment by opening your browser and navigating to [http://localhost](http://localhost).
