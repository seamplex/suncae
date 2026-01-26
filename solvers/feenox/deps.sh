#!/bin/false

feenox_version=1.2.1
feenox_version_min=1.72

# feenox
# Function to extract version from binary
get_feenox_version() {
  local binary="$1"
  "$binary" --version 2>&1 | head -n1 | cut -d" " -f2
}

echo -n "meshers/feenox... "

# Check if feenox is already installed system-wide
use_system_binary=0
if [ -x "$(which feenox 2>/dev/null)" ] && [ $force = 0 ]; then
  installed_version=$(get_feenox_version "$(which feenox)")
  if [ -n "$installed_version" ] && version_ge "$installed_version" "$feenox_version_min"; then
    echo "found system version $installed_version (>= $feenox_version_min), using it"
    use_system_binary=1
    # Create symlink to system binary
    mkdir -p bin
    ln -sf "$(which feenox)" bin/feenox
  else
    echo "system version $installed_version is too old (need >= $feenox_version_min), will download"
  fi
fi

if [ $use_system_binary = 0 ]; then
  feenox_tarball=feenox-${feenox_version}-linux-amd64
  if [ $force = 1 ] || [ ! -x bin/feenox ] || [ ! -f deps/${feenox_tarball}.tgz ]; then
    cd deps
    if [ ! -e  ${feenox_tarball}.tar.gz ]; then
      wget -q -c https://www.seamplex.com/feenox/dist/linux/${feenox_tarball}.tar.gz
    fi
    tar xzf ${feenox_tarball}.tar.gz
    cp ${feenox_tarball}/bin/feenox  ../bin
    cp ${feenox_tarball}/bin/fee2ccx ../bin
    echo "done"
    cd ..
  else
    echo "already installed"
  fi
fi

