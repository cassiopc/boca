# Networking for boca-docker

This demo shows how to add network isolation between services in the _boca-docker_ application. Docker's networking subsystem uses drivers to provide specific networking functionality. In the example, we use the _bridge_ network driver (default). It is usually used when applications run in standalone containers that need to communicate.
If deploying the application on a swarm cluster, it will be necessary to edit the `tests/networks/docker-compose.yml` file and set the _overlay_ network driver to allow the communication between services/containers on different Docker daemons. For more information, refer to the [documentation](https://docs.docker.com/network/#network-drivers).

## Example

* Launch the application:

**... via docker compose**

  ```sh
  docker compose -f tests/networks/docker-compose.yml up -d
  ```

**... or docker stack deploy (set the _overlay_ network driver first)**

  ```sh
  docker stack deploy --compose-file tests/networks/docker-compose.yml boca-stack-networks
  ```

* Open a web browser and visit the URL [http://localhost:8000/boca](http://localhost:8000/boca). To login use the default credentials (Name: _system_ | Password: _boca_);

* To bring it down:

**... via docker compose**

  ```sh
  docker compose -f tests/networks/docker-compose.yml down
  ```

**... or docker stack rm**

  ```sh
  docker stack rm boca-stack-networks
  ```
