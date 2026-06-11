#!/bin/sh
[ -d .git ] || exit 0

for hook in scripts/hooks/*; do
    name=$(basename "$hook")
    cp "$hook" ".git/hooks/$name"
    chmod +x ".git/hooks/$name"
    echo "Installed git hook: $name"
done
