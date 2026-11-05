# Healthcheck for boca-docker

This demo shows how to make the `boca-web` and `boca-jail` services wait for the database container (`boca-db`) to startup and be ready to accept requests before continuing. The `healthcheck` has been configured to periodically check if PostgreSQL is ready using the [`pg_isready`](https://www.postgresql.org/docs/14/app-pg-isready.html) command.
If the check is successful the container will be marked as `healthy`. Until then it will remain in an `unhealthy` state. For more details about the parameters `interval`, `timeout`, `retries`, and `start_period` see the documentation [here](https://docs.docker.com/engine/reference/builder/#healthcheck).

> **NOTE:** `depends_on` with `healthcheck` does not work when used with `docker stack deploy`. For more information, see the discussion thread [here](https://github.com/docker/compose/issues/4305).

## Example

* Launch the application:

**... via docker compose**

  ```sh
  docker compose -f tests/healthcheck/docker-compose.yml up -d
  ```

* Open a web browser and visit the URL [http://localhost:8000/boca](http://localhost:8000/boca). To login use the default credentials (Name: _system_ | Password: _boca_).

* To bring it down:

**... via docker-compose**

  ```sh
  docker compose -f tests/healthcheck/docker-compose.yml down
  ```
