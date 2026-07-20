#!/bin/bash -x

if [ ! -e ./case.fee ]; then
  echo "error: run from case directory"
  exit 1
fi
if [ ! -d ./run ]; then
  echo "error: run dir does not exist"
  exit 1
fi
if [ -z "${1}" ]; then
  echo "error: specify the problem type"
  exit 1
fi

write_json() {
  local path="${1}"
  local tmp="${path}.tmp.$$"
  cat > "${tmp}"
  mv "${tmp}" "${path}"
}


# https://stackoverflow.com/questions/3679296/only-get-hash-value-using-md5sum-without-filename
# A simple array assignment works... Note that the first element of a Bash array can be addressed by just the name without the [0] index, i.e., $md5 contains only the 32 characters of md5sum.

problem_type=${1}
problem_hash=($(md5sum case.fee))
mesh_hash=($(md5sum mesh.geo))
started_at=$(date --iso-8601=seconds)

write_json run/${problem_hash}.json << EOF
{
  "status": "running",
  "pid": $$,
  "started_at": "${started_at}"
}
EOF

# cad=$(yq .cad case.yaml | tr -d \")
cad=$(grep  ^cad case.yaml | cut -f2 -d: | tr -d " ")
max_length=$(jq .max_length ../../cads/${cad}/cad.json)

cp case.fee run/${problem_hash}.fee || exit 2
cd run || exit 3

# check the syntax
../../../../../bin/feenox -c ${problem_hash}.fee 1> ${problem_hash}.1 2> ${problem_hash}.2
if [ $? -eq 0 ]; then
  # all good!
  # see if we have the second-order mesh
  if [ ! -e meshes/${mesh_hash}-2.msh ]; then
    ../../../../../bin/gmsh -3 meshes/${mesh_hash}.msh  -order 2 -o meshes/${mesh_hash}-2.msh > meshes/${mesh_hash}-2.1
  fi
  
  # run
  # TODO: time & memory (maybe we can read it from the log)
  ../../../../../bin/feenox --progress ${problem_hash}.fee 1> ${problem_hash}.1 2> ${problem_hash}.2
  feenox_error=$?
 
  if [ $feenox_error -eq 0 ]; then
    if [ "x${problem_type}" = "xmechanical" ]; then
      ../../../../../bin/feenox ../../../../../solvers/feenox/second2first.fee  ${problem_hash} ${mesh_hash}
      ../../../../../bin/feenox ../../../../../solvers/feenox/displacements.fee ${problem_hash} ${max_length} | tr -s ' \t\n' ' ' > ${problem_hash}-displacements.dat
      ../../../../../bin/feenox ../../../../../solvers/feenox/field1.fee        ${problem_hash} sigma         | tr -s ' \t\n' ' ' > ${problem_hash}-sigma.dat
    elif [ "x${problem_type}" = "xheat_conduction" ]; then
      ../../../../../bin/feenox ../../../../../solvers/feenox/field.fee         ${problem_hash} T             | tr -s ' \t\n' ' ' > ${problem_hash}-T.dat
    fi
    status="success"
  else
    status="error" 
  fi
else
  status="syntax_error"
fi

write_json ${problem_hash}.json << EOF
{
  "status": "${status}",
  "started_at": "${started_at}"
}
EOF

# sync run/meshes/${mesh_hash}.json
rm -f solving.pid
