# Optional virtual boot rehearsal; this image is never put on a participant USB.
FROM seminfo/client-builder:22.04
RUN apt-get update && apt-get install -y --no-install-recommends \
    qemu-system-x86 seabios ovmf socat \
    && rm -rf /var/lib/apt/lists/*
