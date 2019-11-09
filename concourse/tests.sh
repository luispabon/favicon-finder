#!/usr/bin/env bash

# Ensure we exit with failure if anything here fails
set -e

INITIAL_FOLDER=`pwd`

# cd into the codebase, as per CI source
cd code
mkdir reports

# Install xdebug & disable
apt-get update
apt-get install -y php-xdebug make
phpdismod xdebug

composer ${COMPOSER_ARGS}

# Static analysis, unit tests
make all

# Go back to initial working dir to allow outputs to function
cd ${INITIAL_FOLDER}

# Copy reports to output (only of output is defined)
[ -d "coverage-reports"  ] && cp code/reports/* coverage-reports/ -Rf || exit 0
