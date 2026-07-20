<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

include("../conf.php");
include("../auths/{$auth}/auth.php");
include("common.php");
suncae_require_post_csrf();
include("case.php");

chdir($case_dir);
$problem_meta["status"] = "not_running";

// first, see if the problem is finished or running
$problem_json_path = "run/{$problem_hash}.json";
[$problem_status, $error] = suncae_read_json_file_with_retries($problem_json_path, "problem meta");
if ($error != "") {
  return_error_json($error);
  exit();
}

if (isset($problem_status["pid"]) && suncae_cancel_process_group($problem_status["pid"])) {
  $problem_meta["status"] = "canceled";
  if (isset($problem_status["started_at"])) {
    $problem_meta["started_at"] = $problem_status["started_at"];
  }
  suncae_write_json_file($problem_json_path, $problem_meta);
}

return_back_json($problem_meta);
