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

$json_dir = runner_tmpdir();
$json_path = "{$json_dir}/meta.json";
assert_true(suncae_write_json_file($json_path, ["status" => "running", "pid" => 123]), "atomic json write returns true");
$json = json_decode(file_get_contents($json_path), true);
assert_true(is_array($json) && $json["status"] == "running" && $json["pid"] == 123, "atomic json write persists valid json");
runner_rmrf($json_dir);

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