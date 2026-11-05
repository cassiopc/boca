# :balloon: boca-docker

[![Build and publish multi-platform_Docker images on ghcr.io workflow][build_publish_workflow_badge]][build_publish_workflow_link]
[![Delete GitHub Actions cache for repository workflow][cache_cleanup_workflow_badge]][cache_cleanup_workflow_link]
[![Delete_untagged_and/or_unsupported_Docker_images_on_ghcr.io_workflow][packages_cleanup_workflow_badge]][packages_cleanup_workflow_link]
[![Close_stale_issues_and_PRs_workflow][close_stale_workflow_badge]][close_stale_workflow_link]

[![Ubuntu JAMMY][ubuntu_jammy_badge]][ubuntu_jammy_link]
[![Ubuntu FOCAL][ubuntu_focal_badge]][ubuntu_focal_link]
[![Multi-Architecture][arch_badge]][arch_link]

[![Google_Groups][groups_badge]][groups_link]

[build_publish_workflow_badge]: https://img.shields.io/github/actions/workflow/status/joaofazolo/boca-docker/ci.yml?label=build%20images&logo=github
[build_publish_workflow_link]: https://github.com/joaofazolo/boca-docker/actions?workflow=CI "build and publish multi-platform images"
[cache_cleanup_workflow_badge]: https://img.shields.io/github/actions/workflow/status/joaofazolo/boca-docker/clean-cache.yml?label=clean%20cache&logo=github
[cache_cleanup_workflow_link]: https://github.com/joaofazolo/boca-docker/actions?workflow=delete%20GitHub "delete github actions cache"
[packages_cleanup_workflow_badge]: https://img.shields.io/github/actions/workflow/status/joaofazolo/boca-docker/clean-packages.yml?label=clean%20packages&logo=github
[packages_cleanup_workflow_link]: https://github.com/joaofazolo/boca-docker/actions?workflow=delete%20untagged "delete untagged/unsupported images"
[close_stale_workflow_badge]: https://img.shields.io/github/actions/workflow/status/joaofazolo/boca-docker/close-stale.yml?label=close%20stale&logo=github
[close_stale_workflow_link]: https://github.com/joaofazolo/boca-docker/actions?workflow=close%20stale "close stale issues and prs"
[ubuntu_jammy_badge]: https://img.shields.io/badge/ubuntu-jammy-E95420.svg?logo=Ubuntu
[ubuntu_focal_badge]: https://img.shields.io/badge/ubuntu-focal-E95420.svg?logo=Ubuntu
[ubuntu_jammy_link]: https://hub.docker.com/_/ubuntu/tags?page=1&name=jammy "ubuntu:jammy image"
[ubuntu_focal_link]: https://hub.docker.com/_/ubuntu/tags?page=1&name=focal "ubuntu:focal image"
[arch_badge]: https://img.shields.io/badge/multi--arch-%20amd64%20|%20arm/v7%20|%20arm64/v8%20|%20ppc64le%20|%20s390x%20-lightgray.svg?logo=Docker&logoColor=white
[arch_link]: #how-to-run-on-different-ubuntu-release-images "multi-arch images"
[groups_badge]: https://img.shields.io/badge/join-boca--users%20group-blue.svg?logo=data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAAXNSR0IArs4c6QAAAJBlWElmTU0AKgAAAAgABgEGAAMAAAABAAIAAAESAAMAAAABAAEAAAEaAAUAAAABAAAAVgEbAAUAAAABAAAAXgEoAAMAAAABAAIAAIdpAAQAAAABAAAAZgAAAAAAAABIAAAAAQAAAEgAAAABAAOgAQADAAAAAQABAACgAgAEAAAAAQAAACCgAwAEAAAAAQAAACAAAAAAF9yy1AAAAAlwSFlzAAALEwAACxMBAJqcGAAAAm1pVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IlhNUCBDb3JlIDYuMC4wIj4KICAgPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICAgICAgPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIKICAgICAgICAgICAgeG1sbnM6dGlmZj0iaHR0cDovL25zLmFkb2JlLmNvbS90aWZmLzEuMC8iPgogICAgICAgICA8dGlmZjpQaG90b21ldHJpY0ludGVycHJldGF0aW9uPjI8L3RpZmY6UGhvdG9tZXRyaWNJbnRlcnByZXRhdGlvbj4KICAgICAgICAgPHRpZmY6WFJlc29sdXRpb24+NzI8L3RpZmY6WFJlc29sdXRpb24+CiAgICAgICAgIDx0aWZmOllSZXNvbHV0aW9uPjcyPC90aWZmOllSZXNvbHV0aW9uPgogICAgICAgICA8dGlmZjpPcmllbnRhdGlvbj4xPC90aWZmOk9yaWVudGF0aW9uPgogICAgICAgICA8dGlmZjpDb21wcmVzc2lvbj4xPC90aWZmOkNvbXByZXNzaW9uPgogICAgICAgICA8dGlmZjpSZXNvbHV0aW9uVW5pdD4yPC90aWZmOlJlc29sdXRpb25Vbml0PgogICAgICA8L3JkZjpEZXNjcmlwdGlvbj4KICAgPC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KsVruIwAABUVJREFUWAnlV81v3EQUfzP22Nn1pmmQ0kqVUAqCSlA4ckNlScKh4sApSPwBSEUExAlIAqoDNO0R0SIqlb+gPXKgICVZ8XGCG0olBFLUQxBN0kTVrr27/pjhvbG96/V+sFJ7QGKiWb9583vf88YOwP99sHESsLiojP2zoLEz26Bu3WLxILlxcYNkh/AUI6XFzYSncs6PiytqgiSqfjZxyABTRM1f9l5QcTxPNDOMjY1l5xeiEww9x8ElGELnRy6KPJvoxIG59fpVQ1SWeJoHicmPw8a1zZXJd0ClWWBMjcTlgila4UUGrauuMimquc8a79mTlaU48KKw6YU0iSYe7QEapvmvONSV6Oy3NtCBmssiDWVwIfQ1SZkS6WSax+CtjjqkR+AuEK6jsyOUEP0loLRiVC9eVtNW3PiTGeZjSsZ0FjKsYtxgyDtqm81ZUmNHpbvImx6Ii6PDwKg89dMyO9Ilo6zlRn8GUoAWYGyfcawGgMzJyJS3//MHM3WauDcchzq0LlJQME6sfgeQ2amXguuirB0gr8N0Ks1T8BWuk4H0CNx1AnV0piLZI0trts49sy7wrpp2eYmlXaCwC6K2j13g9HTBy5fq18RE5W3qFvJW4dGJghQ3oguGO+C6vAoXOR2e+fX6s0rCOfKOcfhhY2XyDkVUuwgxrAGrYiYJt/Bp/RnJ4CVKrDLNH7c+tLer7hbiqvGg9Gt99FMcdNNl1+35L5TdbNSf4JKdADBB8nCvVJnc+fZd1s7LLVw5nIrBOm0oYzpWKhRx8Nf3Hx/f0Rg62OgouCx/lvRWXwYWb6Lx1/GudxWfE/4yJvNNbk7MGlZSgziIQUatu5iLG5urzqWFK2oqjrxPGIM3DKs8w01UiTWIggCUDPHNwT5H3NdkzUWdbsGJHgcy46+43qlYqO8sx3kuaoWg4kCiTu09CnAwLG6VBTw4OPjVMMypyvTxpwOvjQYjwuAktcrgosQQCkHDu70XOa9tuyygwPKZ6DqQ9j/WtsKF95s54ZyOWqg1uYBy3UKXH5dBGEY7+3uWxTnMnjgZcM5NpVB5fjA8Iwoiq+LYgedtbq5U5vPbRHcEqms1nWNDeF8Kp2PczmMSYfQZDd27v2e127584NflwdGBhbcX6sI85YcC0mljBtqYzbn59cb7tI1B6t4mWmcgq838euuMUvHvZAEn7nUTRGAywLEfvWYD/vh7FyzDSJC4c+bU4yCEheuCE7iHWiQYgqs4vOeEzpPfuMxP9Scpq6WZkCp6VZTKKALYxUXjxMaB7EbT14YpYDx8EMYx+C0faTbQAYSR8ciwnZO+7Z0jNYs3k+x3SqB1K3g+9X+odYqwHQaYiV5IG0+91tHL1rzsB48L5eIsrffvJBF2aqFBDJxiGTUfPwHwqX0jB6SUOvKsSOSLxJsqQQz1IEJRUyp1LNWpH70O5HeIxozSn2k7hk5YmnJRmkZjDTCyr5Q4Agt5ZqmEzhWVJGv0zxRY3ahF9e+OUQ6QccBe5mHLu41RHqKYyTHAY0Kpw6ABEotO9mz84CgLBUHTo3etYtmXUmoHI8feBTM+hD0RyhvE1te4S3fr4EHplqLkGGGzsbb10SRCR4/d0duFXSxe+mrucYAOcYqUouyI0PfdzdXJNXqhYPfiVg1n8qxlJHKIXa0SoX+IGDp05Lnvgh4H8IAJcgFvQTv0G9p4cj3TIez9ktEWal072qHucjjl9m7xwnLXLOGRb/urFHlqHMs8wHiv4MOuqKEAzrv3j9H/AFqbPkgJ/2G1jynfNUZX85hCjxo2+F+sR23lP6XvH9zRm0CuC6knAAAAAElFTkSuQmCC
[groups_link]: https://groups.google.com/g/boca-users "boca-users@Google Groups"

## Table of Contents

- [What Is BOCA?](#what-is-boca)
- [Why boca-docker?](#why-boca-docker)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [How To Deploy It To A Swarm](#how-to-deploy-it-to-a-swarm)
- [How To Add Custom Configuration](#how-to-add-custom-configuration)
- [How To Run On Different Ubuntu Release Images](#how-to-run-on-different-ubuntu-release-images)
- [How To Build It (For Development)](#how-to-build-it-for-development)
- [How To Publish It](#how-to-publish-it)
- [How To Contribute](#how-to-contribute)
- [Known Issues](#known-issues)
- [Who Is Using It?](#who-is-using-it)
- [License](#license)
- [Support](#support)

## What Is BOCA?

BOCA Online Contest Administrator (known simply as BOCA) is an administration system to held programming contests (e.g., ACM-ICPC, Maratona de Programação da SBC).
According to the developers, its main features are portability, concurrency control, multi-site and distributed contests, and a simple web interface (for details refer to [https://github.com/cassiopc/boca](https://github.com/cassiopc/boca)).

BOCA is implemented mainly in PHP and makes use of a PostgreSQL database in the backend (see architecture below). It is a good example of a monolithic system, in which the user interface and database access are all interwoven, rather than containing architecturally separate components.
The problem is compound due to the low readability and complex code structuring, which is hard to adapt and to extend.

## Why boca-docker?

The _boca-docker_ project is a use case of how we can take advantage of microservices architecture and containerization technology (i.e., Docker) to deploy applications in a more convenient and faster way (see illustration below).
After quite some reverse engineering, we provide a multi-platform/arch Docker version of BOCA's main components (web app, online automated judge and database) aiming at easing the customization, extensibility and automation of the operational effort required to deploy, run and scale BOCA.

Original architecture | _boca-docker_ architecture
:-------------------------:|:-------------------------:
![Alt text](/imgs/arquitetura-boca.png?raw=true "BOCA architecture")  |  ![Alt text](/imgs/arquitetura-boca-docker.png?raw=true "boca-docker architecture")

This work started as part of the undergraduate final year project carried out by João Vitor Alves Fazolo under supervision of Prof. Dr. Rodrigo Laiola Guimaraes at Universidade Federal do Espirito Santo ([UFES](https://www.ufes.br/)).

## Requirements

- Install [Docker Desktop](https://www.docker.com/get-started);
- Install [Git](https://github.com/git-guides/install-git) (only for building and publishing).

## Quick Start

- Open a Terminal window and make sure the Docker engine is up and running:

  ```sh
  # List docker images
  docker image ls
  # List containers
  docker container ls -a
  ```

- Download the `docker-compose.yml` and `docker-compose.prod.yml` files, and place them in the current work directory. Then,

  ```sh
  docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
  ```

- Voilà! The application should be running now. Open a web browser and visit the URL [http://localhost:8000/boca](http://localhost:8000/boca). First, create and activate a BOCA contest (user: _system_ | password: _boca_). Then, login as admin (user: _admin_ | password: _boca_) to manage users, problems, languages etc;

  > **NOTE:** consider changing these passwords later on. Find out more information on how to setup a contest in the [documentation](https://github.com/cassiopc/boca/tree/master/doc). For general questions about BOCA consider looking at this [forum](https://groups.google.com/g/boca-users).

- To stop the application (considering that the shell is in the same directory):

  ```sh
  docker compose -f docker-compose.yml -f docker-compose.prod.yml down
  ```

## How To Deploy It To A Swarm

- Create the stack (make sure Docker Engine is already running in [swarm mode](https://docs.docker.com/engine/swarm/swarm-mode/)):

  ```sh
  docker stack deploy --compose-file docker-compose.yml -c docker-compose.prod.yml boca-stack
  ```

- Then, check if the stack is running:

  ```sh
  docker stack services boca-stack
  ```

- Open a web browser and follow the instructions described above;

- To bring the stack down:

  ```sh
  docker stack rm boca-stack
  ```

## How To Add Custom Configuration

There are many ways to customize the _boca-docker_ application. Without trying to support every possible use case, here are just a few that we have found useful.

- **Environment Variables:** shows the correct syntax for the various ways one can change predefined configuration values to keep the _boca-docker_ application flexible and organized. See [documentation](tests/env/README.md);

- **Docker Secrets:** an alternative way to passing sensitive information via environment variables, causing the initialization scripts to load the values for those variables from files present in the containers. See [documentation](tests/secrets/README.md);

- **Networking:** shows how to add network isolation between services in the _boca-docker_ application. See [documentation](tests/networks/README.md);

- **Volumes:** demonstrates how to store data outside the database container, so that the state of the application persists even after the container stops. See [documentation](tests/volumes/README.md);

- **Migrations:** illustrates how to backup and restore BOCA's database to facilitate migration from one _boca-docker_ instance to another. See [documentation](tests/migrations/README.md);

- **Healthcheck:** allows a check to be configured in order to determine whether or not the PostgreSQL container is "healthy." This is a particularly neat use case given that the other services depend on that to work. See [documentation](tests/healthcheck/README.md);

- **Multiple Platforms:** shows the syntax for selecting an image that matches a specific OS and architecture (alternatively, Docker does that automatically). See [documentation](tests/platforms/README.md).

## How To Run On Different Ubuntu Release Images

To run the _boca-docker_ application built on top of different versions of Ubuntu images, please edit the `docker-compose.prod.yml` file with an alternative tag from the table below.

Tag name | BOCA version | Ubuntu version | Code name | Architecture
-------- | ------------ | -------------- | --------- | ------------
`latest`, `1.2`, `1.2-jammy`, `1.2.2`, `1.2.2-jammy` | 1.5 | 22.04 LTS | Jammy Jellyfish | `amd64`, `arm/v7`, `arm64/v8`, `ppc64le`, `s390x`
`1.2-focal`, `1.2.2-focal` | 1.5 | 20.04 LTS | Focal Fossa | `amd64`, `arm/v7`, `arm64/v8`, `ppc64le`, `s390x`
`nightly`, `nightly-jammy` | 1.5 | 22.04 LTS | Jammy Jellyfish | `amd64`, `arm/v7`, `arm64/v8`, `ppc64le`, `s390x`
`nightly-focal` | 1.5 | 20.04 LTS | Focal Fossa | `amd64`, `arm/v7`, `arm64/v8`, `ppc64le`, `s390x`

For example, to use BOCA version 1.5 running on Ubuntu 20.04 LTS (Focal Fossa) on any supported architecture:

  ```sh
  ...
  services:
    ...

    # web app
    boca-web:
        image: ghcr.io/joaofazolo/boca-docker/boca-web:1.2-focal
    ...

    ...
    # online judge
    boca-jail:
        image: ghcr.io/joaofazolo/boca-docker/boca-jail:1.2-focal
    ...
  ```

### Deprecated Image Tags

The following image tags have been deprecated and are no longer receiving updates:

- 1.2.1
- 1.2.0
- 1.1.0
- 1.0.0

## How To Build It (For Development)

- Clone this repository and set it as your working directory:

  ```sh
  git clone https://github.com/joaofazolo/boca-docker.git
  cd boca-docker
  ```

- Then, build the images (this might take a while, sit back and relax):

  ```sh
  docker build -t boca-base . -f docker/dev/base/Dockerfile
  docker build -t boca-web . -f docker/dev/web/Dockerfile
  docker build -t boca-jail . -f docker/dev/jail/Dockerfile
  ```

  > **NOTE:** Keep in mind that these Docker images are created for and to run on the default platform (i.e., `linux/amd64`). This works for the majority of development machines and cloud provider versions. To build target-specific or multi-platform Docker images consult the [documentation](https://docs.docker.com/build/building/multi-platform/);

- To compose it up use the command below:

  ```sh
  docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
  ```

- Follow the instructions [above](#quick-start) to set up the application;

- To stop it:

  ```sh
  docker compose -f docker-compose.yml -f docker-compose.dev.yml down
  ```

## How To Publish It

> **NOTE:** These instructions take into account the Docker images generated in the previous section (no multi-platform support).

- After building, set the user and image tags accordingly. The IMAGE_ID's will show up with the `docker image ls`;

  ```sh
  docker image ls
  # boca-base only necessary for development
  # docker tag IMAGE_ID_BOCA_BASE ghcr.io/joaofazolo/boca-docker/boca-base:1.2.2
  docker tag IMAGE_ID_BOCA_WEB ghcr.io/joaofazolo/boca-docker/boca-web:1.2.2
  docker tag IMAGE_ID_BOCA_JAIL ghcr.io/joaofazolo/boca-docker/boca-jail:1.2.2
  ```

- Log in into GitHub's Container Registry using your username and personal access token (see [documentation](https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry#authenticating-to-the-container-registry));

  ```sh
  docker login ghcr.io
  ```

- Push the container images to repository.

  ```sh
  # boca-base only necessary for development
  # docker push ghcr.io/joaofazolo/boca-docker/boca-base:1.2.2
  docker push ghcr.io/joaofazolo/boca-docker/boca-web:1.2.2
  docker push ghcr.io/joaofazolo/boca-docker/boca-jail:1.2.2
  ```

## How To Contribute

If you would like to help contribute to this project, please see [CONTRIBUTING](https://github.com/joaofazolo/boca-docker/blob/master/CONTRIBUTING.md).

Before submitting a PR consider building and testing a Docker image locally and checking your code with Super-Linter:

  ```sh
  docker run --rm \
             -e ACTIONS_RUNNER_DEBUG=true \
             -e RUN_LOCAL=true \
             --env-file ".github/super-linter.env" \
             -v "$PWD":/tmp/lint \
             ghcr.io/super-linter/super-linter:latest
  ```

## Known Issues

- Rosetta for x86_64/amd64 emulation must be disabled on Apple Silicon
(ARM-based chips) for the online automated judge (boca-jail) to work (tested
on Apple M1, Docker Desktop 4.28.0, Engine: 25.0.3, Compose: v2.24.6-desktop.1,
Mar 2024);

## Who Is Using It?

<p align="left">
  <a href="https://www.ufes.br/" target="_blank">
    <img src="https://upload.wikimedia.org/wikipedia/commons/1/18/Marca_Ufes_SVG.svg" alt="Ufes" height="100" hspace="10" align="top" />
  </a>
  <a href="https://www.unisinos.br/" target="_blank">
    <img src="https://upload.wikimedia.org/wikipedia/commons/3/3b/Unisinos_symbol_and_logo_%28horizontal%29.svg" alt="Unisinos" height="70" hspace="10" align="top" />
  </a>
</p>

## License

Copyright Universidade Federal do Espirito Santo (Ufes)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.

This program is released under license GNU GPL v3+ license.

## Support

Please report any issues with _boca-docker_ at [https://github.com/joaofazolo/boca-docker/issues](https://github.com/joaofazolo/boca-docker/issues)
