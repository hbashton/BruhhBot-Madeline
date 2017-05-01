#!/bin/bash

until php bot.php; do
    echo "The bot crashed with exit code $?.  Respawning.." >&2
    [ $(ls | grep -c bot.madeline) -eq 1 ] && rm bot.madeline
    sleep 2
done
