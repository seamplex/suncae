#!/bin/false

ccx_version=2.22

# ccx
echo -n "solvers/ccx... "
if [ $force = 1 ] || [ ! -x bin/ccx ]; then
  cd deps
  ccx_tarball=ccx-${ccx_version}-linux-static
  if [ ! -e  ${ccx_tarball}.tar.gz ]; then
    wget -c https://www.seamplex.com/suncae/deps/${ccx_tarball}.tar.gz
  fi
  tar xzf ${ccx_tarball}.tar.gz
  cp ${ccx_tarball}/ccx ../bin
  echo "done"
  cd ..
else
 echo "already installed"
fi
