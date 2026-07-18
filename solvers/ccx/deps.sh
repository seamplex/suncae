#!/bin/false

ccx_version=2.22

# ccx
echo -n "solvers/ccx... "
# the one from http://www.dhondt.de/ccx_2.22.tar.bz2 needs libgfortran4
# so I packed this one as a static binary
ccx_tarball=ccx-${ccx_version}-linux-static
if [ $force = 1 ] || [ ! -x bin/ccx ] || [ ! -f deps/${ccx_tarball}.tar.gz ]; then
  cd deps
  if [ ! -e  ${ccx_tarball}.tar.gz ]; then
    wget -q -c https://www.seamplex.com/suncae/deps/${ccx_tarball}.tar.gz
  fi
  tar xzf ${ccx_tarball}.tar.gz
  cp ${ccx_tarball}/ccx ../bin
  echo "done"
  cd ..
else
 echo "already installed"
fi
