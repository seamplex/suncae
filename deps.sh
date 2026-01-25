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
for i in wget tar unzip python3; do
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


# Function to compare versions (include in main deps.sh or source from a utils file)
version_ge() {
  printf '%s\n%s\n' "$2" "$1" | sort -V -C
  return $?
}


# TODO: parse conf.php
. renderers/x3dom/deps.sh
. uxs/faster-than-quick/deps.sh
. meshers/gmsh/deps.sh
. solvers/feenox/deps.sh
. solvers/ccx/deps.sh
