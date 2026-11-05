FROM ubuntu:22.04
ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y --no-install-recommends \
    xorriso squashfs-tools rsync curl ca-certificates gnupg \
    python3 openssl dosfstools e2fsprogs parted gdisk nftables iproute2 \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /work
