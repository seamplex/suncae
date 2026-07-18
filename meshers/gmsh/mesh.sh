#!/bin/bash -x

usage="case"
if [ ! -z "${1}" ]; then
  usage="cad"
fi

if [ "x${usage}" == "xcase" ]; then
  dir="run"
  mesh="mesh"
  if [ ! -d ${dir}/meshes ]; then
    echo "error: ${dir}/meshes dir does not exist"
    exit 1
  fi
else
  dir="."
  mesh="default"
  if [ ! -d ${dir}/meshes ]; then
    mkdir -p ${dir}/meshes
  fi
fi

if [ ! -e ./${mesh}.geo ]; then
  echo "error: run from case directory"
  exit 1
fi
  
write_json() {
  local path="${1}"
  local tmp="${path}.tmp.$$"
  cat > "${tmp}"
  mv "${tmp}" "${path}"
}

mesh_hash=($(md5sum ${mesh}.geo))

write_json ${dir}/meshes/${mesh_hash}.json << EOF
{
  "status": "running",
  "pid": $$,
  "started_at": "$(date --iso-8601=seconds)"
}
EOF

# TODO: time & memory (maybe we can read it from the log)
../../../../bin/gmsh -check ${mesh}.geo                                   1> ${dir}/meshes/${mesh_hash}.1 2> ${dir}/meshes/${mesh_hash}.2
if [ $? -eq 0 ]; then
  ../../../../bin/gmsh -3   ${mesh}.geo -o ${dir}/meshes/${mesh_hash}.msh 1> ${dir}/meshes/${mesh_hash}.1 2> ${dir}/meshes/${mesh_hash}.2
  
  # the meshing could have worked or not, that's in $?
  gmsh_error=$?
  if [ "x${gmsh_error}" != "x0" ]; then
    echo ${dir}/meshes/${mesh_hash}.2 > tmp
    cat  ${dir}/meshes/${mesh_hash}.2 | grep -v "No elements"  | \
                                        grep -v "\-\-\-\-\-\-\-\-\-\-\-" | \
                                        grep -v "Mesh generation error summary" | \
                                        grep -v " warning" | \
                                        grep -v " errors" | \
                                        grep -v "Check the full log for details" > tmp
                                        
    sed -n 's/.*intersection (\([^)]*\)).*/\1/p' tmp | tr ',' ' ' | head -n1 > ${dir}/meshes/${mesh_hash}.intersections
    mv tmp ${dir}/meshes/${mesh_hash}.2
  fi
  
  # we can have a partial mesh, though
  # TODO: rewrite mesh_data in C++
  if [ -e ${dir}/meshes/${mesh_hash}.msh ]; then
    ../../../../meshers/gmsh/mesh_data.py ${mesh_hash} ${dir}/meshes  > ${dir}/meshes/${mesh_hash}-data.log
  fi
  
  # the metadata depends on whether the mesh worked or not
  ../../../../meshers/gmsh/mesh_meta.py ${dir}/meshes/${mesh_hash} ${gmsh_error} > ${dir}/meshes/${mesh_hash}.json
  if [ -e ${dir}/meshes/${mesh_hash}.gp ]; then
    # TODO: bin
    gnuplot ${dir}/meshes/${mesh_hash}.gp
  fi
# TODO: should we remove this guy?  
#   rm -f ${dir}/meshes/${mesh_hash}-status.json

else
  write_json ${dir}/meshes/${mesh_hash}.json << EOF
{
  "status": "syntax_error"
}
EOF
fi

# sync run/meshes/${mesh_hash}.json
rm -f ${dir}/meshing.pid
