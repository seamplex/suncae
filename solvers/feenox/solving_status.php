<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

$problem_hash = $_GET["problem_hash"];
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

if ($results_status["status"] == "running" && isset($results_status["pid"]) && posix_getpgid($results_status["pid"])) {
  
  exec("../../../../solvers/feenox/solve_status.sh {$problem_hash}");
  

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

return_back_json($results_status);
?>
