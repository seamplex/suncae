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


# https://stackoverflow.com/questions/3679296/only-get-hash-value-using-md5sum-without-filename
# A simple array assignment works... Note that the first element of a Bash array can be addressed by just the name without the [0] index, i.e., $md5 contains only the 32 characters of md5sum.

problem_type=${1}
problem_hash=($(md5sum case.fee))
mesh_hash=($(md5sum mesh.geo))

cat << EOF > run/${problem_hash}.json
{
  "status": "running",
  "pid": $$
}
EOF

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

  # convert from .fee to .inp
  ../../../../../bin/fee2ccx ${problem_hash}.fee > ${problem_hash}.inp
  
  # run
  ../../../../../bin/ccx -i ${problem_hash} 1> ${problem_hash}.1 2> ${problem_hash}.2
  ccx_error=$?
 
  if [ $ccx_error -eq 0 ]; then
    if [ "x${problem_type}" = "xmechanical" ]; then
      ../../../../../bin/feenox ../../../../../solvers/ccx/frd2vtk.fee          ${problem_hash}
      ../../../../../bin/feenox ../../../../../solvers/feenox/second2first.fee  ${problem_hash} ${mesh_hash}
      ../../../../../bin/feenox ../../../../../solvers/feenox/displacements.fee ${problem_hash} ${max_length} | tr -s ' \t\n' ' ' > ${problem_hash}-displacements.dat
      ../../../../../bin/feenox ../../../../../solvers/feenox/field1.fee        ${problem_hash} sigma         | tr -s ' \t\n' ' ' > ${problem_hash}-sigma.dat
    fi
    status="success"
  else
    status="error" 
  fi
else
  status="syntax_error"
fi

cat << EOF > ${problem_hash}.json
{
  "status": "${status}"
}
EOF

# sync run/meshes/${mesh_hash}.json
rm -f ${dir}/${problem_hash}.pid
