<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

$cad_hash = $_GET["cad_hash"];

// assume everything's fine
$response["status"] = "ok";
$response["username"] = $username;
$response["error"] = "";

if (isset($username) == false || $username == "") {
  return_error_json("username is empty");
}

$cad_dir = $data_dir . "{$username}/cads/{$cad_hash}";
if (file_exists($cad_dir) === false) {
  if (mkdir($cad_dir, 0755, true) === false) {
    return_error_json("cannot mkdir {$cad_dir}");
  }
}
if (chdir($cad_dir) === false) {
  return_error_json("cannot chdir to {$cad_dir}");
}

// ------------------------------------------------------------
if (file_exists("cad.json") === false) {
  exec(sprintf("%s/cadimport.py 2>&1", __DIR__), $output, $error_level);

  // TODO: keep output
  if ($error_level != 0) {
    $error_message = "Error {$error_level} when importing CAD: ";
    for ($i = 0; $i < count($output); $i++) {
      $error_message .= $output[$i];
    }
    return_error_json($error_message);
  }
}

// ------------------------------------------------------------
if (file_exists("cad.json")) {
  $cad = json_decode(file_get_contents("cad.json"), true);
  $response["position"] = $cad["position"];
  $response["orientation"] = $cad["orientation"];
  $response["centerOfRotation"] = $cad["centerOfRotation"];
  $response["fieldOfView"] = $cad["fieldOfView"];
  
} else {
  return_error_json("cannot create CAD json");  
}


// ------------------------------------------------------------
// leave running the mesher in the background
exec("../../../../cadprocessors/gmsh/initial_mesh.sh > cadmesh.log 2>&1 &");

if ($response["error"] != "") {
  return_error_json("CAD {$cad_hash} process failed: {$response["error"]}");
} else {
  return_back_json($response);
}

