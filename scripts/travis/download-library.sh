#!/usr/bin/env bash

case ${DISTRIBUTION} in
    "thunder")
        docroot="docroot"
    ;;
    *)
        docroot="web"
esac

wget https://github.com/select2/select2/archive/master.zip
unzip  master.zip
mkdir /tmp/test-install/${docroot}/libraries
mv select2-master /tmp/test-install/${docroot}/libraries/select2
