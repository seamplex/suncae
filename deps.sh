#!/bin/bash -e

force=0
if [ "x${1}" = "x--force" ]; then
  force=1
fi

if [ ! -e deps.sh ]; then
  echo "run deps.sh from the root directory, i.e."
  echo "\$ ./deps.sh"
  exit 1
fi

# check for needed tools
for i in wget tar unzip patchelf python3; do
  if [ -z "$(which $i)" ]; then
    echo "error: ${i} not installed"
    exit 1
  fi
done

if [ ! -d data ]; then
  mkdir -p data
  chmod 0777 data
fi

mkdir -p deps

# TODO: parse conf.php
. renderers/x3dom/deps.sh
. uxs/faster-than-quick/deps.sh
. meshers/gmsh/deps.sh
. solvers/feenox/deps.sh
. solvers/ccx/deps.sh


