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
$mesh_meta["status"] = "not_running";

// first, see if the mesh is finished or running
$mesh_json_path = "run/meshes/{$mesh_hash}.json";
[$mesh_status, $error] = suncae_read_json_file_with_retries($mesh_json_path, "mesh meta");
if ($error != "") {
  return_error_json($error);
  exit();
}

if (isset($mesh_status["pid"]) && suncae_cancel_process_group($mesh_status["pid"])) {
  $mesh_meta["status"] = "canceled";
  if (isset($mesh_status["started_at"])) {
    $mesh_meta["started_at"] = $mesh_status["started_at"];
  }
  suncae_write_json_file($mesh_json_path, $mesh_meta);
}

return_back_json($mesh_meta);
