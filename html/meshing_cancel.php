<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

include("../conf.php");
include("../auths/{$auth}/auth.php");
include("common.php");
include("case.php");

chdir($case_dir);
$mesh_meta["status"] = "not_running";

// first, see if the mesh is finished or running
$mesh_json_path = "run/meshes/{$mesh_hash}.json";
if (file_exists($mesh_json_path) === false) {
  // maybe there's some locking thing here
  usleep(200);
  if (file_exists($mesh_json_path) === false) {
    return_error_json("mesh meta json {$mesh_json_path} does not exist");
    exit();
  }
}
if (($mesh_status = json_decode(file_get_contents($mesh_json_path), true)) == null) {
  // maybe there's some locking thing here
  usleep(200);
  if (($mesh_status = json_decode(file_get_contents($mesh_json_path), true)) == null) {
    return_error_json("cannot decode mesh meta json");
    exit();
  }
}

if (isset($mesh_status["pid"]) && posix_getpgid($mesh_status["pid"])) {
  posix_kill($mesh_status["pid"], 15);
  sleep(1);
  $mesh_meta["status"] = "canceled";
  file_put_contents($mesh_json_path, json_encode($mesh_meta));
}

return_back_json($mesh_meta);
