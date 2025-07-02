<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

$cad_hash = $_GET["cad_hash"];

// assume everything's fine
$response["status"] = "ok";
$response["username"] = $username;
$response["error"] = "";

$cad_dir = "../../data/{$username}/cads/{$cad_hash}";
if (file_exists($cad_dir) === false) {
  mkdir($cad_dir, $permissions, true);
}
chdir($cad_dir);

// ------------------------------------------------------------
if (file_exists("cad.json") === false) {
  exec("../../../../cadprocessors/gmsh/cadimport.py 2>&1", $output, $error_level);
  
  // TODO: keep output
  if ($error_level != 0) {
    $response["status"] = "error";
    $response["error"] = "Error {$error_level} when importing CAD: ";
    for ($i = 0; $i < count($output); $i++) {
      $response["error"] .= $output[$i];
    }
    suncae_log("CAD {$cad_hash} process {$response["status"]} {$response["error"]}");
    return_back_json($response);
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
  $response["status"] = "error";
  $response["error"] = "Cannot create CAD json.";
  suncae_log("CAd {$cad_hash} process {$response["status"]} {$response["error"]}");
  return_back_json($response);
}


// ------------------------------------------------------------
// leave running the mesher in the background
exec("../../../../cadprocessors/gmsh/initial_mesh.sh > cadmesh.log 2>&1 &");

suncae_log("CAD {$cad_hash} process {$response["status"]} {$response["error"]}");


return_back_json($response);

