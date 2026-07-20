<?php

// this is included from change_step.php
if (chdir($case_dir) == false) {
  $response["status"] = "error";
  $response["error"] = "cannot chdir to case dir";
}
exec("../../../../bin/feenox -c case.fee 2> run/{$problem_hash}-check.2", $output, $result);
if ($result == 0) {
  $pid = suncae_local_job_start("../../../../solvers/ccx/solve.sh " . escapeshellarg($problem), "run/{$problem_hash}-solve.log", "run/solving.pid", $output, $result);
  if ($pid > 0) {
    $results_meta["status"] = "running";
    $results_meta["pid"] = $pid;
    $results_meta["started_at"] = date("c");
    suncae_log("{$id} problem running pid {$pid}");
  } else {
    $results_meta["status"] = "error";
    suncae_log_error("{$id} cannot launch problem job");
  }
} else {
  $results_meta["status"] = "syntax_error";
  suncae_log("{$id} problem syntax error");
}


if (isset($response["error"]) && $response["error"] != "") {
  suncae_log_error("case {$id} chage step failed: {$response["error"]}");
}

?>
