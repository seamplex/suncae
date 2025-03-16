#!/bin/false

gmsh_version=4.13.1

# gmsh
echo -n "meshers/gmsh... "
#   gmsh_tarball=gmsh-nox-git-Linux64-sdk
gmsh_tarball=gmsh-${gmsh_version}-Linux64-sdk
if [ $force = 1 ] || [ ! -x  bin/gmsh ] || [ ! -f deps/${gmsh_tarball}.tgz ]; then
  cd deps
  if [ ! -e  ${gmsh_tarball}.tgz ]; then
    wget -c http://gmsh.info/bin/Linux/${gmsh_tarball}.tgz
  fi
  tar xzf ${gmsh_tarball}.tgz
  cp ${gmsh_tarball}/bin/gmsh ../bin
  cp ${gmsh_tarball}/lib/gmsh.py ../bin
  cp -d ${gmsh_tarball}/lib/libgmsh.so* ../bin
  cd ../bin
  # this is needed to add pwd to the binary's rpath
  patchelf --set-rpath ${PWD} gmsh
  echo "done"
  cd ..
else
 echo "already installed"
fi
