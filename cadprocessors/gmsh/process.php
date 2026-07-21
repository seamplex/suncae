<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

$cad_hash = isset($_POST["cad_hash"]) ? suncae_require_hash($_POST["cad_hash"], "cad hash") : suncae_error("missing cad hash");
$treatment_mode = isset($_POST["treatment_mode"]) ? suncae_require_field_name($_POST["treatment_mode"], "treatment mode") : "single_material";
$allowed_modes = array("keep", "single_material", "multi_material");
if (in_array($treatment_mode, $allowed_modes, true) === false) {
  return_error_json("invalid treatment mode");
}

// assume everything's fine
$response["status"] = "ok";
$response["username"] = $username;
$response["error"] = "";

if (isset($username) == false || $username == "") {
  return_error_json("username is empty");
}

$owner = suncae_require_path_component($username, "owner");
$base_cad_dir = $data_dir . "{$owner}/cads/{$cad_hash}";
if (file_exists($base_cad_dir) === false) {
  return_error_json("cannot find CAD {$cad_hash}");
}

if (chdir($base_cad_dir) === false) {
  return_error_json("cannot chdir to {$base_cad_dir}");
}

if (file_exists("original.step") === false) {
  return_error_json("cannot find original STEP file for CAD {$cad_hash}");
}

if (file_exists("original.json") === false) {
  $output = array();
  exec(sprintf("%s/cadcheck.py 2>&1", __DIR__), $output, $error_level);
  if ($error_level != 0) {
    return_error_json("cannot inspect CAD {$cad_hash}");
  }
}

$original = json_decode(file_get_contents("original.json"), true);
if ($original == null || isset($original["solids"]) === false) {
  return_error_json("cannot decode original CAD metadata");
}

$solids = intval($original["solids"]);
$response["original_solids"] = $solids;
if ($solids <= 0) {
  return_error_json("CAD contains no solids. SunCAE currently requires at least one 3D solid.");
}

$effective_mode = ($solids > 1) ? $treatment_mode : "keep";
if ($solids > 1 && $effective_mode == "keep") {
  $effective_mode = "single_material";
}
$response["effective_mode"] = $effective_mode;

if ($solids > 1) {
  $fused_hash = md5($cad_hash . "|single_material");
  $fused_cad_dir = $data_dir . "{$owner}/cads/{$fused_hash}";
  if (file_exists($fused_cad_dir) === false && mkdir($fused_cad_dir, 0755, true) === false) {
    return_error_json("cannot mkdir {$fused_cad_dir}");
  }

  $fused_step = "{$fused_cad_dir}/original.step";
  if (file_exists($fused_step) === false) {
    $input_step = "{$base_cad_dir}/original.step";
    $command = sprintf(
      "python3 %s %s %s %s 2>&1",
      escapeshellarg(__DIR__ . "/cadtreat.py"),
      escapeshellarg("single_material"),
      escapeshellarg($input_step),
      escapeshellarg($fused_step)
    );
    $output = array();
    exec($command, $output, $error_level);
    if ($error_level != 0) {
      $error_message = "Error {$error_level} when fusing CAD geometry";
      if (count($output) > 0) {
        $error_message .= ": " . implode(" ", $output);
      }
      return_error_json($error_message);
    }

    @unlink("{$fused_cad_dir}/original.json");
    @unlink("{$fused_cad_dir}/cad.json");
    @unlink("{$fused_cad_dir}/cad.xao");
    @unlink("{$fused_cad_dir}/cad.x3d");
    @unlink("{$fused_cad_dir}/entities.json");
    @unlink("{$fused_cad_dir}/default.geo");
  }

  if (chdir($fused_cad_dir) === false) {
    return_error_json("cannot chdir to {$fused_cad_dir}");
  }

  if (file_exists("original.json") === false) {
    $output = array();
    exec(sprintf("%s/cadcheck.py 2>&1", __DIR__), $output, $error_level);
    if ($error_level != 0) {
      return_error_json("cannot inspect fused CAD {$fused_hash}");
    }
  }

  $fused_original = json_decode(file_get_contents("original.json"), true);
  if ($fused_original == null || isset($fused_original["solids"]) === false) {
    return_error_json("cannot decode fused CAD metadata");
  }
  $response["single_material_solids"] = intval($fused_original["solids"]);

  if (chdir($base_cad_dir) === false) {
    return_error_json("cannot chdir to {$base_cad_dir}");
  }
}

$target_hash = $cad_hash;
$target_cad_dir = $base_cad_dir;

if ($effective_mode != "keep") {
  $target_hash = md5($cad_hash . "|" . $effective_mode);
  $target_cad_dir = $data_dir . "{$owner}/cads/{$target_hash}";
  if (file_exists($target_cad_dir) === false && mkdir($target_cad_dir, 0755, true) === false) {
    return_error_json("cannot mkdir {$target_cad_dir}");
  }

  $target_step = "{$target_cad_dir}/original.step";
  if (file_exists($target_step) === false) {
    $input_step = "{$base_cad_dir}/original.step";
    $command = sprintf(
      "python3 %s %s %s %s 2>&1",
      escapeshellarg(__DIR__ . "/cadtreat.py"),
      escapeshellarg($effective_mode),
      escapeshellarg($input_step),
      escapeshellarg($target_step)
    );
    $output = array();
    exec($command, $output, $error_level);
    if ($error_level != 0) {
      $error_message = "Error {$error_level} when treating CAD geometry";
      if (count($output) > 0) {
        $error_message .= ": " . implode(" ", $output);
      }
      return_error_json($error_message);
    }

    @unlink("{$target_cad_dir}/original.json");
    @unlink("{$target_cad_dir}/cad.json");
    @unlink("{$target_cad_dir}/cad.xao");
    @unlink("{$target_cad_dir}/cad.x3d");
    @unlink("{$target_cad_dir}/entities.json");
    @unlink("{$target_cad_dir}/default.geo");
  }

  $response["source_cad_hash"] = $cad_hash;
}

if (chdir($target_cad_dir) === false) {
  return_error_json("cannot chdir to {$target_cad_dir}");
}

if (file_exists("original.json") === false) {
  $output = array();
  exec(sprintf("%s/cadcheck.py 2>&1", __DIR__), $output, $error_level);
  if ($error_level != 0) {
    return_error_json("cannot inspect treated CAD {$target_hash}");
  }
}

$target_original = json_decode(file_get_contents("original.json"), true);
if ($target_original == null || isset($target_original["solids"]) === false) {
  return_error_json("cannot decode treated CAD metadata");
}
$response["solids"] = intval($target_original["solids"]);

// ------------------------------------------------------------
if (file_exists("cad.json") === false) {
  $output = array();
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
  $response["cad_hash"] = $target_hash;
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

