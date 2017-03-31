#!/bin/bash

git submodule update
[ $(git remote | grep -c BruhhBot-Madeline) -eq 0 ] && git remote add BruhhBot-Madeline https://github.com/hbashton/BruhhBot-Madeline.git
git fetch BruhhBot-Madeline && git checkout BruhhBot-Madeline/master
