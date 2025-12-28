<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

if (!isset($id)) {
  $response["error"] = "Cannnot proceed, no id given.";
  suncae_log_error("mesh {$id} failed: {$response["error"]}");
  return_back_json($response);
  exit();
}

$mesh_data_path = "../data/{$owner}/cads/{$case["cad"]}/meshes/{$mesh_hash}-data.json";
if (file_exists($mesh_data_path)) {
  header("Content-Type: application/json");
  echo file_get_contents($mesh_data_path);
} else {
  $response["nodes"] = "";
  $response["surfaces_edges_set"] = "";
  $response["surfaces_faces_set"] = "";
  return_back_json($response);
}
