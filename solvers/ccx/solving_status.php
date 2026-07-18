<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

$problem_hash = isset($_GET["problem_hash"]) ? suncae_require_hash($_GET["problem_hash"], "problem hash") : suncae_error("missing problem hash");
chdir("../data/{$owner}/cases/{$id}");

// first, see if the solve is finished or running
$results_json_path = "run/{$problem_hash}.json";
[$results_status, $error] = suncae_read_json_file_with_retries($results_json_path, "results meta");
if ($error != "") {
  return_error_json($error);
  exit();
}
$results_meta = $results_status;

if ($results_status["status"] == "running" && isset($results_status["pid"]) && suncae_pid_is_running(intval($results_status["pid"]))) {
  
  exec("../../../../solvers/ccx/solve_status.sh " . escapeshellarg($problem_hash));
  

  $results_json_path = "run/{$problem_hash}-status.json";  
  [$results_status, $error] = suncae_read_json_file_with_retries($results_json_path, "results status", 1);
  if ($error != "") {
    return_error_json($error);
    exit();
  }

}
$results_status = suncae_enrich_solve_status($results_status, $results_meta, $problem_hash, "ccx", "Solving with CalculiX");

return_back_json($results_status);
?>
