<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

$permissions = 0755;
$id = (isset($_POST["id"])) ? $_POST["id"] : ((isset($_GET["id"])) ? $_GET["id"] : "");

function suncae_error($error) {
  echo "<p><b>SunCAE found a fatal error:</b>";
  echo $error;
  echo "</p>";
  exit();
}
  

function return_back_html($response) {
  header("Content-Type: text/html");
  echo $response;
  exit();
}

function return_error_html($error) {
  header("Content-Type: text/html");
  echo $response;
  exit();
}

function return_back_json($response) {
  header("Content-Type: application/json");
  echo json_encode($response);
  exit();
}

function return_error_json($error) {
  $response["error"] = $error;
  suncae_log($error);
  return_back_json($response);
  exit();
}

// based on original work from the PHP Laravel framework
if (!function_exists('str_contains')) {
  function str_contains($haystack, $needle) {
    return $needle !== '' && mb_strpos($haystack, $needle) !== false;
  }
}

function suncae_log($message) {
  global $permissions;
  global $username;
  $log_dir = __DIR__ . "/../data/logs/";
  if (file_exists($log_dir) ==  false) {
    if (mkdir($log_dir, $permissions, true) == false) {
      echo "error: cannot create log directory";
      exit();
    }
  }
  $log = fopen($log_dir . date("Y-m-d").".log", "a");
  if ($log === false) {
    echo "Cannot open log file, please check permissions.";
    exit(1);
  }
  fprintf($log, "%s %s\t%s: %s\n", date("c"), $_SERVER['REMOTE_ADDR'], $username, $message);
  fclose($log);
}
