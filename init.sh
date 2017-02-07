#!/bin/bash
git submodule update
composer update
cd MadelineProto
git am -3 < ../patches/0001-Remove-log-spam.patch
composer update
git revert f7f80241f2769d0f3159f646a31a4ab0be6feb8c