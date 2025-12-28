<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

if (($cad_json = file_get_contents("../data/{$owner}/cads/{$case["cad"]}/cad.json")) == false) {
  return_error_json("cannot find cad {$case["cad"]}");
}

if (($cad = json_decode($cad_json, true)) == null) {
  return_error_json("cannot decode cad {$id}");
}


$mesh_hash = $_GET["mesh_hash"];
chdir("../data/{$owner}/cases/{$id}");

// first, see if the mesh is finished or running
$mesh_json_path = "run/meshes/{$mesh_hash}.json";
if (file_exists($mesh_json_path) === false) {
  // maybe there's some locking thing here
  usleep(200);
  if (file_exists($mesh_json_path) === false) {
    return_error_json("mesh meta json {$mesh_json_path} does not exist");
  }
}
if (($mesh_status = json_decode(file_get_contents($mesh_json_path), true)) == null) {
  // maybe there's some locking thing here
  usleep(200);
  if (($mesh_status = json_decode(file_get_contents($mesh_json_path), true)) == null) {
    // maybe there's some locking thing here
    usleep(200);
    if (($mesh_status = json_decode(file_get_contents($mesh_json_path), true)) == null) {
      // maybe there's some locking thing here
      usleep(200);
      if (($mesh_status = json_decode(file_get_contents($mesh_json_path), true)) == null) {
        return_error_json("");
      }
    }
  }
}

if ($mesh_status["status"] == "running" && (isset($mesh_status["pid"]) && posix_getpgid($mesh_status["pid"]))) {
  exec("../../../../meshers/gmsh/mesh_status.sh {$mesh_hash}");

  $mesh_json_path = "run/meshes/{$mesh_hash}-status.json";  
  if (file_exists($mesh_json_path) === false) {
    return_error_json("mesh status json does not exist");
    exit();
  }
  if (($mesh_status = json_decode(file_get_contents($mesh_json_path), true)) == null) {
    return_error_json("cannot decode mesh status json {$mesh_json_path}");
    exit();
  }

  $mesh_status["progress_edges"]   = round(100 * $mesh_status["edges"] / $cad["edges"]);
  $mesh_status["progress_faces"]   = round(100 * $mesh_status["faces"] / $cad["faces"]);
  $mesh_status["progress_volumes"] = round(100 * $mesh_status["volumes"] / $cad["solids"]);
  $mesh_status["progress_data"] = round(100 * $mesh_status["data"] / 4);
}

return_back_json($mesh_status);
?>
