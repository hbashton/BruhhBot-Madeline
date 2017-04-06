#!/bin/bash
git submodule update
composer update || composer.phar update
. start.sh