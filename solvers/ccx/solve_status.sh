#!/bin/bash

if [ -z "${1}" ]; then
  problem_hash=($(md5sum case.fee))
else
  problem_hash=${1}
fi

write_json() {
  local path="${1}"
  local tmp="${path}.tmp.$$"
  cat > "${tmp}"
  mv "${tmp}" "${path}"
}

status=$(jq -r .status run/${problem_hash}.json) || exit 1
if [ "x${status}" == "xrunning" ]; then
  feenox_pid=$(jq -r .pid run/${problem_hash}.json)
  echo $feenox_pid
  if [ -z "$(ps --no-headers --pid ${feenox_pid})" ]; then
    echo "solver pid ${feenox_pid} is not running"
    echo "TODO: check if it worked or not"
    exit 1
  fi
  
  mesh=0
  meshfile=$(grep MESH case.fee | awk '{print $4}' | head -n1)
  meshname=$(basename ${meshfile} .msh)
  if [ -e run/meshes/${meshname}.1 ]; then
    mesh=$(grep % run/meshes/${meshname}.1 | tr -d [] | awk '{print $3}' | tr -d % | tail -n1)
  fi

  logfile=run/${problem_hash}.1
  build=$(grep \\. ${logfile}  | tr -d '\n' | wc -c)
  solve=$(grep \\- ${logfile}  | tr -d '\n' | wc -c)
  post=$(grep = ${logfile}  | tr -d '\n' | wc -c)
  data=50

  phase="prepare_mesh"
  phase_label="Preparing second-order mesh"
  if [ ${post} -gt 0 ]; then
    phase="postprocess"
    phase_label="Post-processing results"
  elif [ ${solve} -gt 0 ]; then
    phase="solve"
    phase_label="Solving linear system"
  elif [ ${build} -gt 0 ]; then
    phase="assemble"
    phase_label="Assembling matrix"
  elif [ ${mesh} = 100 ]; then
    phase="assemble"
    phase_label="Assembling matrix"
  fi

  done_mesh=0
  if [ ${mesh} = 100 ]; then
    done_mesh=1
  fi
  done_build=0
  if [ ${build} = 100 ]; then
    done_build=1
  fi
  done_solve=0
  if [ ${solve} = 100 ]; then
    done_solve=1
  fi
  done_post=0
  if [ ${post} = 100 ]; then
    done_post=1
  fi
  
  done_data=0
  
  write_json run/${problem_hash}-status.json << EOF
{
  "status": "running",
  "pid": ${feenox_pid},
  "phase": "${phase}",
  "phase_label": "${phase_label}",
  "mesh": ${mesh},
  "build": ${build},
  "solve": ${solve},
  "post": ${post},
  "data": ${data},
  "done_mesh": ${done_mesh},
  "done_build": ${done_build},
  "done_solve": ${done_solve},
  "done_post": ${done_post},
  "done_data": ${done_data}
}
EOF
#   sync run/${problem_hash}.json
  
else
  exit 0
fi
