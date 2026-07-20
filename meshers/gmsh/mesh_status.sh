#!/bin/bash

if [ -z "${1}" ]; then
  mesh_hash=($(md5sum mesh.geo))
else
  mesh_hash=${1}
fi

write_json() {
  local path="${1}"
  local tmp="${path}.tmp.$$"
  cat > "${tmp}"
  mv "${tmp}" "${path}"
}

status=$(jq -r .status run/meshes/${mesh_hash}.json) || exit 1
if [ "x${status}" == "xrunning" ]; then
  gmsh_pid=$(jq -r .pid run/meshes/${mesh_hash}.json)
  echo $gmsh_pid
  if [ -z "$(ps --no-headers --pid ${gmsh_pid})" ]; then
    echo "mesher pid ${gmsh_pid} is not running"
    echo "TODO: check if it worked or not"
    exit 1
  fi
  
  logfile=run/meshes/${mesh_hash}.1
  edges=$(grep "Meshing curve"   ${logfile} | tail -n1 | tr -d '[]' | awk '{print $6}') 
  if [ -z "${edges}" ]; then
    edges="0"
  fi
  faces=$(grep "Meshing surface" ${logfile} | tail -n1 | tr -d '[]' | awk '{print $6}') 
  if [ -z "${faces}" ]; then
    faces="0"
  fi
  volumes=$(grep "Meshing 3D..." ${logfile} | wc -l)

  done_edges=$(grep 'Done meshing 1D'    ${logfile} | wc -l)
  done_faces=$(grep 'Done meshing 2D'    ${logfile} | wc -l)
  done_volumes=$(grep 'Done meshing 3D'  ${logfile} | wc -l)
  
  data=0
  datalogfile=run/meshes/${mesh_hash}-data.log
  if [ -e ${datalogfile} ]; then
#     data=$(cat ${datalogfile} | wc -l)
    data=$(tail -n1 ${datalogfile})
  fi
  if [ ${data} = 3 ]; then
    done_data=1
  else
    done_data=0
  fi
  
  write_json run/meshes/${mesh_hash}-status.json << EOF
{
  "status": "running",
  "pid": ${gmsh_pid},
  "edges": ${edges},
  "faces": ${faces},
  "volumes": ${volumes},
  "data": ${data},
  "done_edges": ${done_edges},
  "done_faces": ${done_faces},
  "done_volumes": ${done_volumes},
  "done_data": ${done_data}
}
EOF
#   sync run/meshes/${mesh_hash}.json
  
else
  exit 0
fi
