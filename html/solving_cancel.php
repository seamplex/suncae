<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

include("../conf.php");
include("../auths/{$auth}/auth.php");
include("common.php");
include("case.php");

chdir($case_dir);

// first, see if the problem is finished or running
$problem_json_path = "run/{$problem_hash}.json";
if (file_exists($problem_json_path) === false) {
  // maybe there's some locking thing here
  usleep(200);
  if (file_exists($problem_json_path) === false) {
    return_error_json("problem meta json {$problem_json_path} does not exist");
    exit();
  }
}
if (($problem_status = json_decode(file_get_contents($problem_json_path), true)) == null) {
  // maybe there's some locking thing here
  usleep(200);
  if (($problem_status = json_decode(file_get_contents($problem_json_path), true)) == null) {
    return_error_json("cannot decode problem meta json");
    exit();
  }
}

if (isset($problem_status["pid"]) && posix_getpgid($problem_status["pid"])) {
  posix_kill($problem_status["pid"], 15);
  sleep(1);
  $problem_meta["status"] = "canceled";
  file_put_contents($problem_json_path, json_encode($problem_meta));
}

return_back_json($problem_meta);
