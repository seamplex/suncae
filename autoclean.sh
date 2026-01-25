#!/bin/false
# WARNING! WARNING! WARNING!
#
#  This script will delete your custom cads and cases.
#  Run it explicitly with 
#
#   $ sh autoclean.sh
#
#  It does not have execution permissions to avoid running it by mistake.
#
# WARNING! WARNING! WARNING!


if [ ! -d auths ]; then
  echo "error: run $0 from SunCAE's root directory"
  echo 1
fi

for i in bin deps data; do
  echo -n "cleaning ${i}... "
  rm -rf ${i} || exit 1
  echo "ok"
done

pwd=$PWD
for i in $(find . -name .gitignore); do
  dir=$(dirname ${i})
  if [ "x${dir}" != "x." ]; then
    echo "cleaning ${dir}"
    cd ${dir}
    cat .gitignore | sed '/^#.*/ d' | sed '/^\s*$/ d' | sed 's/^/rm -rf /' | bash || exit 1
    cd ${pwd}
  fi
done
