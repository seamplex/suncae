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

# create gitignored directories
mkdir -p deps bin

# this one needs to be either world writable or owned by the user running the web server
# we start with 0777 but a sane admin would change it back to 0744 (or less)
if [ ! -d data ]; then
  mkdir -p data
  chmod 0777 data
fi



# Function to compare versions
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
