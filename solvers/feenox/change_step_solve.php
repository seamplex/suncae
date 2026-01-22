<?php

// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

// this is included from change_step.php
if (chdir($case_dir) == false) {
  $response["status"] = "error";
  $response["error"] = "cannot chdir to case dir";
}
exec("../../../../bin/feenox -c case.fee 2> run/{$problem_hash}-check.2", $output, $result);
if ($result == 0) {
  exec("../../../../solvers/feenox/solve.sh {$problem} > run/{$problem_hash}-solve.log 2>&1 & echo $! > run/solving.pid");  
  $results_meta["status"] = "running";
  suncae_log("{$id} problem running");
} else {
  $results_meta["status"] = "syntax_error";
  suncae_log_error("{$id} problem syntax error");
}


if ($response["error"] != "") {
  suncae_log_error("case {$id} change step failed \"{$response["error"]}\"");
}
?>
