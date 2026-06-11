#!/usr/bin/env bash
set -e

DATA_DIR="/app/data/compress_full_dataset"
ARCHIVE="/tmp/dataset.tar.zst"
SHA_FILE="/tmp/dataset.sha256"

BASE_URL="https://github.com/RoTak00/roro-thesis-webapp/releases/download/1.0.0"
ARCHIVE_URL="$BASE_URL/dataset.tar.zst"
SHA_URL="$BASE_URL/dataset.sha256"

mkdir -p /app/data

needs_download=false

if [ ! -d "$DATA_DIR" ] || [ -z "$(ls -A "$DATA_DIR" 2>/dev/null)" ]; then
    needs_download=true
elif [ ! -f "$DATA_DIR/.dataset.sha256" ]; then
    needs_download=true
else
    remote_sha="$(curl -fsSL "$SHA_URL" | awk '{print $1}')"
    local_sha="$(cat "$DATA_DIR/.dataset.sha256")"

    if [ "$remote_sha" != "$local_sha" ]; then
        needs_download=true
    fi
fi

if [ "$needs_download" = true ]; then
    echo "Downloading dataset..."

    rm -rf "$DATA_DIR"
    mkdir -p /app/data

    curl -L "$ARCHIVE_URL" -o "$ARCHIVE"
    curl -L "$SHA_URL" -o "$SHA_FILE"

    cd /tmp
    sha256sum -c "$SHA_FILE"

    tar --zstd -xf "$ARCHIVE" -C /app/data

    awk '{print $1}' "$SHA_FILE" > "$DATA_DIR/.dataset.sha256"

    echo "Dataset ready."
else
    echo "Dataset already up to date."
fi