# Data backup and restore in boca-docker

This demo illustrates how to migrate database data from one PostgreSQL instance to another. First, to backup data we use `pg_dump`. This utility dumps BOCA's database and makes consistent backups even if the application is being used concurrently. These dumps can be output either in plain-text script (.sql), archive file or directory formats.
Concomitantly, we illustrate the use of `psql` and `pg_restore` commands to reconstruct BOCA's database to the state it was in at the time the data was saved. On the one hand, `psql` makes use of plain-text files containing SQL commands to rebuild the database during initialization[^1].
On the other hand, `pg_restore` provides a more flexible restore mechanism to examine the archive and/or select which parts of the database are to be restored (for instance, it allows for selection and reordering of all archived items, support parallel restoration, and compression). For more information, refer to the [documentation](https://www.postgresql.org/docs/current/app-pgdump.html).

[^1]: Scripts in the initialization folder only run if starting the database container with a data directory that is empty; otherwise, any pre-existing database will be left untouched on container startup.

> **NOTE:** This example uses [profiles](https://docs.docker.com/compose/profiles/) with Compose which does not work with `docker stack deploy`.

## Example

* Launch the application:

**... via docker compose**

  ```sh
  docker compose -f tests/migrations/docker-compose.yml up -d
  ```

* Open a web browser and visit the URL [http://localhost:8000/boca](http://localhost:8000/boca). To login use the default credentials (Name: _admin_ | Password: _boca_);

  > **NOTE:** The contest restored from the `tests/migrations/backups/boca-db.sql` contains some example [problems](http://localhost:8000/boca/admin/problem.php).

* To backup the database (the dump is saved locally in `/test/migrations/backups/boca-db.tar`):

  > **NOTE:** Try different archive formats by changing the `BOCA_DB_DUMP_FORMAT` and `BOCA_DB_DUMP_FILENAME` variables in the `tests/migrations/docker-compose.yml` file.

**... via docker compose**

  ```sh
  docker compose --profile backup -f tests/migrations/docker-compose.yml up -d
  ```

* To restore the database to an alternative state (the dump used is in `/test/migrations/backups/boca-db.dump`):

**... via docker compose**

  ```sh
  docker compose --profile restore -f tests/migrations/docker-compose.yml up -d
  ```

* To bring it down:

**... via docker compose**

  ```sh
  docker compose --profile backup --profile restore -f tests/migrations/docker-compose.yml down
  ```
