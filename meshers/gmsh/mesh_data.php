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
$mesh_msh_path = "../data/{$owner}/cads/{$case["cad"]}/meshes/{$mesh_hash}.msh";
$mesh_dir = dirname($mesh_data_path);

if (file_exists($mesh_data_path)) {
  header("Content-Type: application/json");
  echo file_get_contents($mesh_data_path);
} else {
  $command = null;
  $candidates = [
    __DIR__ . "/mesh_data_cpp",
    __DIR__ . "/mesh_data",
  ];

  foreach ($candidates as $candidate) {
    if (is_executable($candidate)) {
      $command = escapeshellcmd($candidate) . " " . escapeshellarg($mesh_hash) . " " . escapeshellarg($mesh_dir);
      break;
    }
  }

  if ($command === null) {
    $python = (PHP_OS_FAMILY === "Windows") ? "python" : "python3";
    $python_script = __DIR__ . "/mesh_data.py";
    if (file_exists($python_script)) {
      $command = escapeshellcmd($python) . " " . escapeshellarg($python_script) . " " . escapeshellarg($mesh_hash) . " " . escapeshellarg($mesh_dir);
    }
  }

  if ($command !== null && file_exists($mesh_msh_path)) {
    exec($command, $output, $exit_code);
    if ($exit_code === 0 && file_exists($mesh_data_path)) {
      header("Content-Type: application/json");
      echo file_get_contents($mesh_data_path);
      exit();
    }

    suncae_log_error("mesh {$mesh_hash} data extractor failed: {$command}");
  }

  $response["nodes"] = "";
  $response["surfaces_edges_set"] = "";
  $response["surfaces_faces_set"] = "";
  return_back_json($response);
}
