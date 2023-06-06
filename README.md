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

5. To migrate the database with fresh data, use the following command:

```shell
docker-compose run --rm artisan migrate:fresh --seed
```

6. To run `npm dev`, use the following command:

```shell
docker-compose run --rm npm run dev
```

7. Finally, visit the local development environment by opening your browser and navigating to [http://localhost](http://localhost).

Feel free to explore the features of Artwork and manage your projects effectively!
