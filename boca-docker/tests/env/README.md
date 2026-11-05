# Environment variables for boca-docker

The _boca-docker_ application uses several environment variables which are easy to miss. The variables required are `POSTGRES_PASSWORD` (`boca-db` service) and `BOCA_DB_SUPER_PASSWORD` (`boca-web` service), the rest are optional.

> **NOTE:** DO NOT set the optional `POSTGRES_DB` environment variable in the `boca-db` service. Find out the reasons for that [here](https://github.com/joaofazolo/boca-docker/issues/17).
>
> **NOTE:** From release `1.1.0` some environment variables received a `BOCA_` prefix. These are: `BOCA_DB_HOST`, `BOCA_DB_SUPER_USER`, `BOCA_DB_SUPER_PASSWORD`, `BOCA_DB_USER`, `BOCA_DB_PASSWORD`, and `BOCA_DB_NAME`.

**`BOCA_DB_HOST`**

This optional environment variable, which can be set in the `boca-web` and `boca-jail` services, defines the address/host name of the PostgreSQL server. This is useful if you are connecting to an external server or a docker container named something other than _boca-db_ (default).

**`BOCA_DB_SUPER_USER`**

This optional environment variable is used in conjunction with `BOCA_DB_SUPER_PASSWORD` (`boca-web` service) to manage the database. This environment variable must be the superuser for PostgreSQL. If it is not specified, then the default user of _postgres_ will be used.

> **NOTE:** If specified, this parameter and the `POSTGRES_USER` variable (`boca-db` service) must be set with the same value.

**`BOCA_DB_SUPER_PASSWORD`**

This environment variable is required for the `boca-web` service (web app) to manage the database (`boca-db` service). It must not be empty or undefined. This environment variable must be the superuser password for PostgreSQL. The default superuser is defined by the `BOCA_DB_SUPER_USER` environment variable.

> **NOTE:** It must have the same value of the `POSTGRES_PASSWORD` variable (`boca-db` service).

**`BOCA_DB_USER`** and **`BOCA_DB_PASSWORD`**

Optional in the `boca-web` and `boca-jail` services, these variables are used in conjunction to change the credentials of the unprivileged database user in the web app and online judge, respectively, that can manage the PostgreSQL database but not alter its schema. If they are not specified, then the default values will be used.

**`BOCA_DB_NAME`**

This optional environment variable can be used to define a different name for the default database that is created when the `boca-web` service is first started. If it is not specified, then a default value will be used.

**`BOCA_PASSWORD`**

This optional environment variable can be specified in the `boca-web` service (web app) to define the initial password for the system and admin users. If not set, the default value is used (_boca_). These passwords can be individually updated later on via the web interface.

**`BOCA_KEY`**

This optional environment variable can be specified in the `boca-web` service to set the secret key in HTTP headers. It must be any random large sequence of characters. If it is undefined, then the default value will be used.

**`POSTGRES_USER`**

This optional environment variable is used in conjunction with `POSTGRES_PASSWORD` (`boca-db` service) to set a user and its password. This variable will create the specified user with superuser power and a database with the same name. If it is not specified, then the default user of _postgres_ will be used.

> **NOTE:** Be aware that if this parameter is specified, the `BOCA_DB_SUPER_USER` variable (`boca-web` service) must be defined with the same value.

**`POSTGRES_PASSWORD`**

This environment variable is required to setup the `boca-db` service (PostgreSQL database). It must not be empty or undefined. This environment variable sets the superuser password for PostgreSQL. The default superuser is defined by the `POSTGRES_USER` environment variable.

> **NOTE:** The `BOCA_DB_SUPER_PASSWORD` variable (`boca-web` service) must be set with the same value.

**`BOCA_WEB_HOST`**

This optional environment variable, which can be set in the `boca-jail` service, defines the address/host name of the web application. Although `boca-jail` does not interact with the `boca-web` service directly, the original configuration keeps this reference (not certain why!). Nevertheless, if set, it must be named after the web app service (default: _boca-web_).

## Example

This demo shows different approaches on how to pass environment variables to keep the application secure, flexible and organized.

* Launch the application:

**... via docker compose**

  ```sh
  docker compose --env-file=tests/env/.env -f tests/env/docker-compose.yml up -d
  ```

**... or docker stack deploy**

  ```sh
  export $(grep -v '^#' tests/env/.env | xargs) && docker stack deploy --compose-file tests/env/docker-compose.yml boca-stack-env
  ```

* Open a web browser and visit the URL [http://localhost:8000/boca](http://localhost:8000/boca). To login with the _system_ user, use as password the value of the `BOCA_PASSWORD` variable set in the `tests/env/docker-compose.yml` file (sensitive information stored on GitHub for demo purposes only).

* To bring it down:

**... via docker compose**

  ```sh
  docker compose -f tests/env/docker-compose.yml down
  ```

**... or docker stack rm**

  ```sh
  docker stack rm boca-stack-env
  ```
