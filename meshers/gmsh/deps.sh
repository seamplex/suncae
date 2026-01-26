#!/bin/false

gmsh_version=4.14.0
gmsh_min_version=4.13.0  # Minimum required version for xao support

# Function to extract version from binary
get_gmsh_version() {
  local binary="$1"
  "$binary" --version | head -1
}

# gmsh
echo -n "meshers/gmsh... "

# Check if gmsh is already installed system-wide
use_system_binary=0
if [ -x "$(which gmsh 2>/dev/null)" ] && [ $force = 0 ]; then
  installed_version=$(get_gmsh_version "$(which gmsh)")
  if [ -n "$installed_version" ] && version_ge "$installed_version" "$gmsh_min_version"; then
    echo "found system version $installed_version (>= $gmsh_min_version), using it"
    use_system_binary=1
    # Create symlink to system binary
    mkdir -p bin
    ln -sf "$(which gmsh)" bin/gmsh
  else
    echo "system version $installed_version is too old (need >= $gmsh_min_version), will download"
  fi
fi

if [ $use_system_binary = 0 ]; then
  gmsh_tarball=gmsh-nox-git-Linux64-sdk
#   gmsh_tarball=gmsh-${gmsh_version}-Linux64-sdk
  if [ $force = 1 ] || [ ! -x bin/gmsh ] || [ ! -f deps/${gmsh_tarball}.tgz ]; then
    # check for patchelf
    if [ -z "$(which patchelf)" ]; then
      echo "error: downloaded gmsh needs ${i}, please do sudo apt install patchelf"
      exit 1
    fi
    mkdir -p bin
    cd deps
    if [ ! -e ${gmsh_tarball}.tgz ]; then
      wget -q -c http://gmsh.info/bin/Linux/${gmsh_tarball}.tgz
    fi
    tar xzf ${gmsh_tarball}.tgz
    rm -f ../bin/gmsh.py ../bin/gmsh ../bin/libgmsh.so*
    cp ${gmsh_tarball}/bin/gmsh ../bin
    cp ${gmsh_tarball}/lib/gmsh.py ../bin
    cp -d ${gmsh_tarball}/lib/libgmsh.so* ../bin
    cd ../bin
    patchelf --set-rpath ${PWD} gmsh
    echo "done"
    cd ..
  else
    echo "already installed"
  fi
fi
