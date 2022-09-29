# About Artwork

Artwork is a project organisation tool. 
It enables the scheduling of projects with multiple events, tasks, and responsibilities. 

## Installation

This projects support Docker using Sail, which means `docker-compose up` will give work as well
as `./vendor/bin/sail up`.

After that run the following scripts to install dependencies and compile inertia:

```shell
./vendor/bin/sail npm install
./vendor/bin/sail npx mix
```

To migrate and seed the roles

```shell
./vendor/bin/sail artisan migrate:fresh
./vendor/bin/sail artisan db:seed --class=TestDatabaseSeeder
```

Visit local development on http://localhost 

## Meilisearch

Meilisearch is included in the Docker container and provides searchable indexes. 
The Search Console can be viewed at: `http://0.0.0.0:7700/`

To add Models to the index the artisan commands can be used: 
```shell
./vendor/bin/sail artisan scout:import "App\Models\Department"
./vendor/bin/sail artisan scout:import "App\Models\User"
```

## Debugging

With PHPStorm mind to select the correct Interpreter (laravel.test), install the PEST Plugin and to include 
`SAIL_XDEBUG_MODE=develop,debug,coverage` in your .env file. 

Debugging hint: On Mac set the docker.host to `docker.for.mac.host.internal`. 
`
