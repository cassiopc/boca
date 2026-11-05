# Build matrix for GitHub Actions

The JSON file(s) in this folder will be read in the GitHub Actions workflows and used as shown below.

:warning: Edit the `matrix.json` file to add/remove supported image releases/tags (e.g., `latest`, `1.2`, `1.2.0`), parent/base images (e.g., `ubuntu:jammy`, `ubuntu:focal`), and platforms (e.g., `linux/amd64`, `linux/arm/v7`, `linux/ppc64le`). **Untagged or unsupported build images will be deleted periodically.** :warning:

```yml
jobs:
  # Calling a reusable workflow
  setup:
    uses: ./.github/workflows/read-matrix-file.yml
    with:
        matrix-path: docker/build/matrix.json

  check-setup:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        parent: ${{ fromJSON(needs.setup.outputs.parent) }}
    needs:
      - setup
```
