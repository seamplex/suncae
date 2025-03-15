#!/bin/false

feenox_version=1.0.152

# feenox
echo -n "solvers/feenox... "
if [ $force = 1 ] || [ ! -x  bin/feenox ]; then
  cd deps
  feenox_tarball=feenox-${feenox_version}-linux-amd64
  if [ ! -e  ${feenox_tarball}.tar.gz ]; then
    wget -c https://www.seamplex.com/feenox/dist/linux/${feenox_tarball}.tar.gz
  fi
  tar xzf ${feenox_tarball}.tar.gz
  cp ${feenox_tarball}/bin/feenox  ../bin
  cp ${feenox_tarball}/bin/fee2ccx ../bin
  echo "done"
  cd ..
else
 echo "already installed"
fi
