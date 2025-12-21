<?php

// this is included from change_step.php
if (chdir($case_dir) == false) {
  $response["status"] = "error";
  $response["error"] = "cannot chdir to case dir";
}
exec("../../../../bin/feenox -c case.fee 2> run/{$problem_hash}-check.2", $output, $result);
if ($result == 0) {
  exec("../../../../solvers/ccx/solve.sh {$problem} > run/{$problem_hash}-solve.log 2>&1 & echo $! > run/solving.pid");
  $results_meta["status"] = "running";
  suncae_log("{$id} problem running");
} else {
  $results_meta["status"] = "syntax_error";
  suncae_log("{$id} problem syntax error");
}


if ($response["error"] != "") {
  suncae_log_error("case {$id} chage step failed: {$response["error"]}");
}

?>
