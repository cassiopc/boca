# Multiple platforms for boca-docker

From release `1.2.0` the `boca-web` and `boca-jail` images can support multiple platforms, which means that each single image contains variants for different architectures (all of which are for the Linux operating system).
When pulling images of these services, Docker automatically selects the ones that match the host machine OS and architecture. Conversely, you can make use of the `platform` parameter with the _os[/arch[/variant]]_ syntax to specify which target platform containers for these services will run on.

Architectures currently supported:
- Linux x86-64 (`amd64`)
- ARMv7 32-bit (`arm/v7`)
- ARMv8 64-bit (`arm64/v8`)
- IBM POWER8 (`ppc64le`)
- IBM z Systems (`s390x`)

 As an example, this demo shows the `boca-web` service using the `linux/ppc64le` and the `boca-jail` the `linux/arm/v7` target platform. For more information, refer to the [documentation](https://docs.docker.com/compose/compose-file/compose-file-v2/#platform).

 > **NOTE:** The `platform` parameter does not work when used with `docker stack deploy`.

## Example

* Launch the application:

**... via docker compose**

  ```sh
  docker compose -f tests/platforms/docker-compose.yml up -d
  ```

* Open a web browser and visit the URL [http://localhost:8000/boca](http://localhost:8000/boca). To login use the default credentials (Name: _system_ | Password: _boca_);

* To bring it down:

**... via docker-compose**

  ```sh
  docker compose -f tests/platforms/docker-compose.yml down
  ```
