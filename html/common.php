<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

$permissions = 0777;
$id = (isset($_POST["id"])) ? $_POST["id"] : ((isset($_GET["id"])) ? $_GET["id"] : "");
$data_dir = __DIR__ . "/../data/";
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION["csrf_token"])) {
  $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
if (file_exists($data_dir) === false) {
  if (mkdir($data_dir, $permissions, true) === false) {
    echo "cannot mkdir {$data_dir}, please check permissions";
    exit(1);
  }
}

// based on original work from the PHP Laravel framework
if (!function_exists('str_contains')) {
  function str_contains($haystack, $needle) {
    return $needle !== '' && mb_strpos($haystack, $needle) !== false;
  }
}

function suncae_log_write($file_path, $username, $message) {

  $log = fopen($file_path, "a");
  if ($log) {
    $ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : "localhost";
    fprintf($log, "%s %s\t%s: %s\n", date("c"), $ip, $username, $message);
    fclose($log);
    return 0;
  } else {
    return 1;
  }
}

function suncae_log_error($message, $level = 0) {
  global $permissions;
  global $username;
  if ($username == "") {
    $username = "anonymous";
  }

  $log_dir = __DIR__ . "/../data/logs/";
  if (file_exists($log_dir) ==  false) {
    if (mkdir($log_dir, $permissions, true) == false) {
      exit(1);
    }
  }

  $date = date('Y-m-d');
  suncae_log_write("{$log_dir}error.log", $username, $message);
  suncae_log_write("{$log_dir}{$level}-{$date}.log", $username, $message);
  if ($level > 0) {
    suncae_log_write("{$log_dir}0-{$date}.log", $username, $message);
  }
}


function suncae_error($error) {
  global $username;
  echo $error;
  suncae_log_error($error);
  exit();
}

function suncae_log($message, $level = 0) {
  global $permissions;
  global $username;
  if ($username == "") {
    $username = "anonymous";
  }

  $log_dir = __DIR__ . "/../data/logs/";
  if (file_exists($log_dir) ==  false) {
    if (mkdir($log_dir, $permissions, true) == false) {
      return 1;
    }
  }

  $date = date('Y-m-d');
  suncae_log_write("{$log_dir}0-{$date}.log", $username, $message);
  if ($level > 0) {
    if (suncae_log_write("{$log_dir}{$level}-{$date}.log", $username, $message) != 0) {
      return 1;
    }
  }

  if ($username != "anonymous") {
    $log_dir = __DIR__ . "/../data/{$username}/";
    if (file_exists($log_dir) ==  false) {
      if (mkdir($log_dir, $permissions, true) == false) {
        return 2;
      }
    }
    if (suncae_log_write("{$log_dir}activity.log", $username, $message) != 0) {
      return 1;
    }
  }
  return 0;
}


function return_back_html($response) {
  header("Content-Type: text/html");
  echo $response;
  exit();
}

function return_error_html($error) {
  header("Content-Type: text/html");
  echo $error;
  suncae_log_error($error);
  exit();
}

function return_back_json($response) {
  header("Content-Type: application/json");
  echo json_encode($response);
  exit();
}

function return_error_json($error) {
  $response["status"] = "error";  
  $response["error"] = $error;
  suncae_log_error($error);
  return_back_json($response);
  exit();
}

function suncae_is_hash($value) {
  return is_string($value) && preg_match('/^[a-f0-9]{32}$/', $value) === 1;
}

function suncae_require_hash($value, $name) {
  if (!suncae_is_hash($value)) {
    suncae_error("invalid {$name}");
  }
  return $value;
}

function suncae_require_optional_hash($value, $name) {
  if ($value != "" && !suncae_is_hash($value)) {
    suncae_error("invalid {$name}");
  }
  return $value;
}

function suncae_is_path_component($value) {
  return is_string($value) && preg_match('/^[A-Za-z0-9._@-]+$/', $value) === 1;
}

function suncae_require_path_component($value, $name) {
  if (!suncae_is_path_component($value)) {
    suncae_error("invalid {$name}");
  }
  return $value;
}

function suncae_git_commit_all($message, &$output = null, &$result = null) {
  exec("git commit -a -m " . escapeshellarg($message), $output, $result);
  return $result == 0;
}

function suncae_require_field_name($value, $name) {
  if (!is_string($value) || preg_match('/^[A-Za-z0-9_.-]+$/', $value) !== 1) {
    suncae_error("invalid {$name}");
  }
  return $value;
}

function suncae_require_single_line($value, $name, $max_length = 1024) {
  if (!is_string($value) || strlen($value) > $max_length || preg_match('/[\r\n\x00]/', $value) === 1) {
    suncae_error("invalid {$name}");
  }
  return $value;
}

function suncae_require_numeric_expression($value, $name) {
  if ($value == "remove") {
    return $value;
  }
  $value = suncae_require_single_line($value, $name, 128);
  if (preg_match('/^[0-9eE+\-*\/()., \t]+$/', $value) !== 1) {
    suncae_error("invalid {$name}");
  }
  return $value;
}

function suncae_csrf_token() {
  return $_SESSION["csrf_token"];
}

function suncae_request_csrf_token() {
  if (isset($_POST["csrf_token"])) {
    return $_POST["csrf_token"];
  }
  if (isset($_SERVER["HTTP_X_CSRF_TOKEN"])) {
    return $_SERVER["HTTP_X_CSRF_TOKEN"];
  }
  return "";
}

function suncae_require_post_csrf() {
  if ($_SERVER["REQUEST_METHOD"] != "POST") {
    return_error_json("invalid request method");
  }
  if (!hash_equals(suncae_csrf_token(), suncae_request_csrf_token())) {
    return_error_json("invalid CSRF token");
  }
}

function suncae_write_json_file($path, $data) {
  $tmp_path = sprintf("%s.tmp.%d.%s", $path, getmypid(), bin2hex(random_bytes(4)));
  if (file_put_contents($tmp_path, json_encode($data)) === false) {
    return false;
  }
  return rename($tmp_path, $path);
}
function suncae_tail_file($path, $max_lines = 40, $max_bytes = 8192) {
  if (!file_exists($path) || is_file($path) === false) {
    return "";
  }
  $size = filesize($path);
  $offset = ($size > $max_bytes) ? ($size - $max_bytes) : 0;
  $file = fopen($path, "r");
  if ($file === false) {
    return "";
  }
  fseek($file, $offset);
  $content = stream_get_contents($file);
  fclose($file);
  $lines = explode("\n", $content);
  if ($offset > 0 && count($lines) > 0) {
    array_shift($lines);
  }
  return implode("\n", array_slice($lines, -$max_lines));
}

function suncae_elapsed_seconds($started_at) {
  $started = strtotime($started_at);
  if ($started === false) {
    return 0;
  }
  return max(0, time() - $started);
}

function suncae_pid_is_running($pid) {
  return is_int($pid) && $pid > 1 && posix_getpgid($pid) !== false;
}

function suncae_local_job_command($command) {
  global $runner_time_limit;
  global $runner_memory_limit_kb;
  global $runner_nice;

  $script = "";
  if (isset($runner_memory_limit_kb) && intval($runner_memory_limit_kb) > 0) {
    $script .= "ulimit -v " . intval($runner_memory_limit_kb) . " || exit 125; ";
  }
  $script .= "exec {$command}";
  $limited_command = "sh -c " . escapeshellarg($script);

  if (isset($runner_time_limit) && intval($runner_time_limit) > 0) {
    $limited_command = "timeout --foreground --kill-after=30s " . intval($runner_time_limit) . "s " . $limited_command;
  }
  if (isset($runner_nice) && intval($runner_nice) != 0) {
    $limited_command = "nice -n " . intval($runner_nice) . " " . $limited_command;
  }
  return $limited_command;
}

function suncae_local_job_start($command, $log_path, $pid_path, &$output = null, &$result = null) {
  $shell = "setsid " . suncae_local_job_command($command) . " > " . escapeshellarg($log_path) . " 2>&1 & echo $! > " . escapeshellarg($pid_path);
  exec($shell, $output, $result);
  if ($result != 0 || file_exists($pid_path) === false) {
    return 0;
  }
  $pid = intval(trim(file_get_contents($pid_path)));
  return $pid > 1 ? $pid : 0;
}

function suncae_cancel_process_group($pid) {
  $pid = intval($pid);
  if ($pid <= 1 || suncae_pid_is_running($pid) === false) {
    return false;
  }
  if (@posix_kill(-$pid, 15) === false) {
    @posix_kill($pid, 15);
  }
  usleep(300000);
  if (suncae_pid_is_running($pid)) {
    if (@posix_kill(-$pid, 9) === false) {
      @posix_kill($pid, 9);
    }
  }
  return true;
}
