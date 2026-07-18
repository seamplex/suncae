#!/bin/bash

set -euo pipefail

repo_dir=$(cd "$(dirname "${0}")/../.." && pwd)
failures=0

assert_equals() {
  local expected="${1}"
  local actual="${2}"
  local message="${3}"
  if [ "${expected}" = "${actual}" ]; then
    echo "ok - ${message}"
  else
    echo "not ok - ${message}: expected '${expected}', got '${actual}'"
    failures=$((failures + 1))
  fi
}

run_phase_case() {
  local solver="${1}"
  local log_text="${2}"
  local expected_phase="${3}"
  local expected_label="${4}"
  local tmp
  local problem_hash="0123456789abcdef0123456789abcdef"
  tmp=$(mktemp -d)
  mkdir -p "${tmp}/run/meshes"
  printf 'MESH main = run/meshes/main.msh\n' > "${tmp}/case.fee"
  printf '%s' "${log_text}" > "${tmp}/run/${problem_hash}.1"
  cat > "${tmp}/run/${problem_hash}.json" << EOF
{
  "status": "running",
  "pid": $$
}
EOF

  (cd "${tmp}" && "${repo_dir}/solvers/${solver}/solve_status.sh" "${problem_hash}" > /dev/null)
  local phase
  local label
  phase=$(jq -r .phase "${tmp}/run/${problem_hash}-status.json")
  label=$(jq -r .phase_label "${tmp}/run/${problem_hash}-status.json")
  assert_equals "${expected_phase}" "${phase}" "${solver} reports phase ${expected_phase}"
  assert_equals "${expected_label}" "${label}" "${solver} reports label ${expected_label}"
  rm -rf "${tmp}"
}

for solver in feenox ccx; do
  run_phase_case "${solver}" "" "prepare_mesh" "Preparing second-order mesh"
  run_phase_case "${solver}" "..." "assemble" "Assembling matrix"
  run_phase_case "${solver}" "---" "solve" "Solving linear system"
  run_phase_case "${solver}" "===" "postprocess" "Post-processing results"
done

if [ ${failures} -gt 0 ]; then
  echo "${failures} failure(s)"
  exit 1
fi

echo "all solve status tests passed"