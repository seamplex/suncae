<?php

include(__DIR__ . "/../../conf.php");
include(__DIR__ . "/../../html/common.php");

$failures = 0;

function assert_true($condition, $message) {
  global $failures;
  if ($condition) {
    echo "ok - {$message}\n";
  } else {
    echo "not ok - {$message}\n";
    $failures++;
  }
}

function assert_false($condition, $message) {
  assert_true(!$condition, $message);
}

function runner_tmpdir() {
  $tmp = tempnam(sys_get_temp_dir(), "suncae-test-");
  if ($tmp === false) {
    throw new RuntimeException("cannot create temp path");
  }
  unlink($tmp);
  if (mkdir($tmp) === false) {
    throw new RuntimeException("cannot create temp dir");
  }
  return $tmp;
}

function runner_rmrf($path) {
  if (!is_dir($path)) {
    return;
  }
  foreach (scandir($path) as $entry) {
    if ($entry == "." || $entry == "..") {
      continue;
    }
    $child = "{$path}/{$entry}";
    if (is_dir($child)) {
      runner_rmrf($child);
    } else {
      unlink($child);
    }
  }
  rmdir($path);
}

function assert_no_process_group($pid, $message) {
  exec("ps -o pid= -g " . intval($pid), $ps, $result);
  assert_true($result == 1 && count($ps) == 0, $message);
}

assert_true(suncae_is_hash("0123456789abcdef0123456789abcdef"), "valid md5 hash accepted");
assert_false(suncae_is_hash("0123456789abcdef0123456789abcdeg"), "invalid md5 hash rejected");
assert_true(suncae_is_path_component("gmsh-1.0_alpha"), "safe path component accepted");
assert_false(suncae_is_path_component("../gmsh"), "path traversal component rejected");

$repo_data_dir = __DIR__ . "/../../data";
$repo_data_backup = null;
if (file_exists($repo_data_dir)) {
  $repo_data_backup = __DIR__ . "/../../data-backup-" . bin2hex(random_bytes(4));
  if (rename($repo_data_dir, $repo_data_backup) === false) {
    throw new RuntimeException("cannot back up repo data dir");
  }
}
try {
  $username = "";
  $date = date("Y-m-d");
  assert_true(suncae_log("php common test log line") == 0, "suncae_log creates missing logs directory and reports success");
  assert_true(file_exists("{$repo_data_dir}/logs/0-{$date}.log"), "suncae_log writes the default log entry");

  runner_rmrf($repo_data_dir);
  mkdir($repo_data_dir);
  file_put_contents("{$repo_data_dir}/logs", "blocker");
  assert_true(suncae_log("php common test blocked log line") != 0, "suncae_log returns non-zero when the default log write fails");
} finally {
  runner_rmrf($repo_data_dir);
  if ($repo_data_backup !== null) {
    rename($repo_data_backup, $repo_data_dir);
  }
}

$served_js_path = __DIR__ . "/../../html/js/faster-than-quick";
$source_js_path = __DIR__ . "/../../uxs/faster-than-quick/js";
assert_true(is_link($served_js_path), "served faster-than-quick js is a symlink");
assert_true(realpath($served_js_path) == realpath($source_js_path), "served faster-than-quick js resolves to ux source");

$json_dir = runner_tmpdir();
$json_path = "{$json_dir}/meta.json";
assert_true(suncae_write_json_file($json_path, ["status" => "running", "pid" => 123]), "atomic json write returns true");
$json = json_decode(file_get_contents($json_path), true);
assert_true(is_array($json) && $json["status"] == "running" && $json["pid"] == 123, "atomic json write persists valid json");
[$read_json, $read_error] = suncae_read_json_file_with_retries($json_path, "test json", 1);
assert_true($read_error == "" && $read_json["status"] == "running", "json read with retries returns decoded data");
[$missing_json, $missing_error] = suncae_read_json_file_with_retries("{$json_dir}/missing.json", "missing test", 1);
assert_true($missing_json === null && $missing_error == "missing test json {$json_dir}/missing.json does not exist", "json read with retries reports missing file");
file_put_contents($json_path, "{");
[$invalid_json, $invalid_error] = suncae_read_json_file_with_retries($json_path, "invalid test", 1);
assert_true($invalid_json === null && $invalid_error == "cannot decode invalid test json {$json_path}", "json read with retries reports decode errors");
runner_rmrf($json_dir);

$delete_dir = runner_tmpdir();
file_put_contents("{$delete_dir}/0123456789abcdef0123456789abcdef.1", "one");
file_put_contents("{$delete_dir}/0123456789abcdef0123456789abcdef.2", "two");
file_put_contents("{$delete_dir}/0123456789abcdef0123456789abcdef-extra", "extra");
file_put_contents("{$delete_dir}/keep", "keep");
assert_true(suncae_delete_hashed_artifacts($delete_dir, "0123456789abcdef0123456789abcdef", [".1", ".2"], "test") == "", "hashed artifact delete reports success");
assert_true(file_exists("{$delete_dir}/0123456789abcdef0123456789abcdef.1") === false && file_exists("{$delete_dir}/0123456789abcdef0123456789abcdef.2") === false && file_exists("{$delete_dir}/0123456789abcdef0123456789abcdef-extra") && file_exists("{$delete_dir}/keep"), "hashed artifact delete removes only allowed suffixes");
assert_true(suncae_delete_hashed_artifacts($delete_dir, "not-a-hash", [".1"], "test") == "invalid test artifact hash", "hashed artifact delete validates hash");
assert_true(suncae_delete_hashed_artifacts($delete_dir, "0123456789abcdef0123456789abcdef", ["../bad"], "test") == "invalid test artifact suffix", "hashed artifact delete validates suffixes");
mkdir("{$delete_dir}/0123456789abcdef0123456789abcdef.dir");
$delete_error = suncae_delete_hashed_artifacts($delete_dir, "0123456789abcdef0123456789abcdef", [".dir"], "test");
assert_true(str_starts_with($delete_error, "refusing to delete non-file test artifact"), "hashed artifact delete refuses directories");
rmdir("{$delete_dir}/0123456789abcdef0123456789abcdef.dir");
symlink("{$delete_dir}/keep", "{$delete_dir}/0123456789abcdef0123456789abcdef.link");
$delete_error = suncae_delete_hashed_artifacts($delete_dir, "0123456789abcdef0123456789abcdef", [".link"], "test");
assert_true(str_starts_with($delete_error, "refusing to delete non-file test artifact"), "hashed artifact delete refuses symlinks");
unlink("{$delete_dir}/0123456789abcdef0123456789abcdef.link");
runner_rmrf($delete_dir);

$history_dir = runner_tmpdir();
mkdir("{$history_dir}/run");
mkdir("{$history_dir}/run/meshes");
$mesh_history_hash = "11111111111111111111111111111111";
$solve_history_hash = "22222222222222222222222222222222";
suncae_write_json_file("{$history_dir}/run/meshes/{$mesh_history_hash}.json", ["status" => "success", "started_at" => "2026-07-18T10:00:00+00:00", "nodes" => 1234]);
suncae_write_json_file("{$history_dir}/run/{$solve_history_hash}.json", ["status" => "running", "started_at" => "2026-07-18T11:00:00+00:00", "phase_label" => "Assembling matrix"]);
$history = suncae_case_run_history($history_dir, "gmsh", "feenox");
assert_true(count($history) == 2 && $history[0]["kind"] == "solve" && $history[0]["tool"] == "feenox", "run history sorts latest solve first");
assert_true($history[1]["kind"] == "mesh" && $history[1]["summary"] == "1,234 nodes", "run history includes mesh summary");
assert_true(suncae_elapsed_label(3661) == "1h 1m 1s" && suncae_elapsed_label(61) == "1m 1s", "elapsed label formats durations");
runner_rmrf($history_dir);

$status_dir = runner_tmpdir();
$old_cwd = getcwd();
$problem_hash = "0123456789abcdef0123456789abcdef";
mkdir("{$status_dir}/run");
file_put_contents("{$status_dir}/run/{$problem_hash}.1", "solver stdout\n");
file_put_contents("{$status_dir}/run/{$problem_hash}.2", "solver stderr\n");
chdir($status_dir);
$enriched_status = suncae_enrich_solve_status(
  ["status" => "running", "phase" => "assemble", "phase_label" => "Assembling matrix"],
  ["pid" => getmypid(), "started_at" => date("c")],
  $problem_hash,
  "feenox",
  "Solving with FeenoX"
);
chdir($old_cwd);
assert_true($enriched_status["kind"] == "solve" && $enriched_status["tool"] == "feenox", "solve status enrichment identifies solver job");
assert_true($enriched_status["phase"] == "assemble" && $enriched_status["phase_label"] == "Assembling matrix", "solve status enrichment preserves phase");
assert_true($enriched_status["next_action"] == "Assembling matrix" && $enriched_status["can_cancel"] === true, "solve status enrichment exposes running action");
assert_true(str_contains($enriched_status["log_tail"], "solver stdout") && str_contains($enriched_status["error_tail"], "solver stderr"), "solve status enrichment includes log tails");
runner_rmrf($status_dir);

$old_time_limit = isset($runner_time_limit) ? $runner_time_limit : null;
$old_memory_limit = isset($runner_memory_limit_kb) ? $runner_memory_limit_kb : null;
$old_nice = isset($runner_nice) ? $runner_nice : null;

$runner_time_limit = 60;
$runner_memory_limit_kb = 0;
$runner_nice = 0;

$cancel_dir = runner_tmpdir();
$command = "sh -c " . escapeshellarg("php -r " . escapeshellarg("usleep(30000000);") . " & wait");
$pid = suncae_local_job_start($command, "{$cancel_dir}/job.log", "{$cancel_dir}/job.pid", $output, $result);
assert_true($pid > 1, "local job start returns a process-group pid");
usleep(100000);
$children = trim(shell_exec("pgrep -P " . intval($pid)));
assert_true($children != "", "local job has a child process before cancellation");
assert_true(suncae_cancel_process_group($pid), "process group cancellation reports success");
usleep(200000);
assert_no_process_group($pid, "process group cancellation leaves no running processes");
runner_rmrf($cancel_dir);

$runner_time_limit = 1;
$timeout_dir = runner_tmpdir();
$pid = suncae_local_job_start("php -r " . escapeshellarg("usleep(5000000);"), "{$timeout_dir}/job.log", "{$timeout_dir}/job.pid", $output, $result);
assert_true($pid > 1, "timeout test job starts");
$deadline = microtime(true) + 4;
while (microtime(true) < $deadline && suncae_pid_is_running($pid)) {
  usleep(100000);
}
assert_false(suncae_pid_is_running($pid), "local job timeout stops overlong job");
runner_rmrf($timeout_dir);

if ($old_time_limit === null) {
  unset($runner_time_limit);
} else {
  $runner_time_limit = $old_time_limit;
}
if ($old_memory_limit === null) {
  unset($runner_memory_limit_kb);
} else {
  $runner_memory_limit_kb = $old_memory_limit;
}
if ($old_nice === null) {
  unset($runner_nice);
} else {
  $runner_nice = $old_nice;
}

if ($failures > 0) {
  echo "{$failures} failure(s)\n";
  exit(1);
}

echo "all php common tests passed\n";