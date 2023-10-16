#!/bin/bash

cd "${BASH_SOURCE%/*}" || exit

exitcode=1

rm -rf var/tmp/*
echo "Cleared var/tmp/*"

rm -rf uploads/news/*
echo "Cleared uploads/news/*"

rm -rf uploads/references/*
echo "Cleared uploads/references/*"

rm -rf uploads/database/covers/*
echo "Cleared uploads/database/covers/*"

rm -rf uploads/users/certificates/*
echo "Cleared uploads/users/certificates/*"

rm -rf uploads/users/profile_images/*
echo "Cleared uploads/users/profile_images/*"

rm -rf web/media/cache/*
echo "Cleared web/media/cache/*"

rm -rf web/library/previews/*
echo "Cleared web/library/previews/*"

rm -rf web/library/therapies/*
echo "Cleared web/library/therapies/*"

rm -rf web/library/tracks/*
echo "Cleared web/library/tracks/*"

echo ""
echo "Finished!"