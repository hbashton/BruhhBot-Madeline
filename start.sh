#!/bin/bash

until php bot.php; do
    echo "The bot crashed with exit code $?.  Respawning.." >&2
    sleep 2
done
