<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

$id = (isset($_POST["id"])) ? $_POST["id"] : ((isset($_GET["id"])) ? $_GET["id"] : "");
$data_dir = __DIR__ . "/../data/";

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
  global $username;
  if ($username == "") {
    $username = "anonymous";
  }

  $log_dir = __DIR__ . "/../data/logs/";
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
  global $username;
  if ($username == "") {
    $username = "anonymous";
  }

  $log_dir = __DIR__ . "/../data/logs/";
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
      if (mkdir($log_dir, 0755, true) == false) {
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
  echo $response;
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
