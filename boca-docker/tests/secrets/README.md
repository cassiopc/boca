# Docker Secrets for boca-docker

As an alternative to passing sensitive information via environment variables, `_FILE` may be appended to some of the supported environment variables, causing the initialization scripts to load the values for those variables from files present in the containers. In particular, this can be used to load passwords from Docker secrets stored in `/run/secrets/<secret_name>` files.

Currently, this is supported for `BOCA_DB_SUPER_USER`, `BOCA_DB_SUPER_PASSWORD`, `BOCA_DB_USER`, `BOCA_DB_PASSWORD`, `BOCA_DB_NAME`, `BOCA_PASSWORD`, `BOCA_KEY`, `POSTGRES_PASSWORD`, and `POSTGRES_USER`.

## Example

* Launch the application:

**... via docker compose**

  ```sh
  docker compose -f tests/secrets/docker-compose.yml up -d
  ```

**... or docker stack deploy**

  ```sh
  docker stack deploy --compose-file tests/secrets/docker-compose.yml boca-stack-secrets
  ```

* Open a web browser and visit the URL [http://localhost:8000/boca](http://localhost:8000/boca). To login with the _system_ user, find the password in the `tests/secrets/boca_password.txt` file (stored on GitHub for demo purposes only);

* To bring it down:

**... via docker compose**

  ```sh
  docker compose -f tests/secrets/docker-compose.yml down
  ```

**... or docker stack rm**

  ```sh
  docker stack rm boca-stack-secrets
  ```
