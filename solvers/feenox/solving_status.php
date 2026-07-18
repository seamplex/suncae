<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

$problem_hash = isset($_GET["problem_hash"]) ? suncae_require_hash($_GET["problem_hash"], "problem hash") : suncae_error("missing problem hash");
chdir("../data/{$owner}/cases/{$id}");

// first, see if the solve is finished or running
$results_json_path = "run/{$problem_hash}.json";
if (file_exists($results_json_path) === false) {
  // maybe there's some locking thing here
  usleep(200);
  if (file_exists($results_json_path) === false) {
    return_error_json("results meta json {$results_json_path} does not exist");
    exit();
  }
}
if (($results_status = json_decode(file_get_contents($results_json_path), true)) == null) {
  // maybe there's some locking thing here
  usleep(200);
  if (($results_status = json_decode(file_get_contents($results_json_path), true)) == null) {
    // maybe there's some locking thing here
    usleep(200);
    if (($results_status = json_decode(file_get_contents($results_json_path), true)) == null) {
      return_error_json("");
      exit();
    }
  }
}
$results_meta = $results_status;

if ($results_status["status"] == "running" && isset($results_status["pid"]) && suncae_pid_is_running(intval($results_status["pid"]))) {
  
  exec("../../../../solvers/feenox/solve_status.sh " . escapeshellarg($problem_hash));
  

  $results_json_path = "run/{$problem_hash}-status.json";  
  if (file_exists($results_json_path) === false) {
    return_error_json("results status json does not exist");
    exit();
  }
  if (($results_status = json_decode(file_get_contents($results_json_path), true)) == null) {
    return_error_json("cannot decode results status json {$results_json_path}");
    exit();
  }

}

$results_status["kind"] = "solve";
$results_status["tool"] = "feenox";
$results_status["title"] = "Solving with FeenoX";
$results_status["pid"] = isset($results_meta["pid"]) ? intval($results_meta["pid"]) : (isset($results_status["pid"]) ? intval($results_status["pid"]) : 0);
$results_status["started_at"] = isset($results_meta["started_at"]) ? $results_meta["started_at"] : "";
$results_status["elapsed_seconds"] = ($results_status["started_at"] != "") ? suncae_elapsed_seconds($results_status["started_at"]) : 0;
$results_status["can_cancel"] = ($results_status["status"] == "running" && $results_status["pid"] > 0 && suncae_pid_is_running($results_status["pid"]));
$results_status["can_relaunch"] = in_array($results_status["status"], ["error", "syntax_error", "canceled", "not_running"]);
$results_status["next_action"] = ($results_status["status"] == "running") ? "Wait, cancel, or inspect the log" : (($results_status["status"] == "success") ? "View results" : "Review the log and re-launch solving");
$results_status["log_tail"] = suncae_tail_file("run/{$problem_hash}.1", 25, 8192);
$results_status["error_tail"] = suncae_tail_file(file_exists("run/{$problem_hash}.2") ? "run/{$problem_hash}.2" : "run/{$problem_hash}-check.2", 25, 8192);

return_back_json($results_status);
?>
