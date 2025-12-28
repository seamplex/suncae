<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

if ($id == "") {
  header("Location: new/");
  exit();
}

if ($username == "") {
  suncae_error("empty username");
}

$mesh_hash = (isset($_POST["mesh_hash"])) ? $_POST["mesh_hash"] : ((isset($_GET["mesh_hash"])) ? $_GET["mesh_hash"] : "");
$problem_hash = (isset($_POST["problem_hash"])) ? $_POST["problem_hash"] : ((isset($_GET["problem_hash"])) ? $_GET["problem_hash"] : "");


// TODO: per mesher
function mesh_hash() {
  global $owner, $id;
  // there might have been a chdir to the case's dir
  if (file_exists("mesh.geo")) {
    return md5_file("mesh.geo");
  } else {
    return md5_file("../data/{$owner}/cases/{$id}/mesh.geo");
  }
}


$case_dir = "../data/{$owner}/cases/{$id}";
if (($case_yaml = file_get_contents("{$case_dir}/case.yaml")) == false) {
  suncae_error("cannot find project {$id} for user {$owner}");
}

if (($case = yaml_parse($case_yaml)) == null) {
  suncae_error("cannot decode project {$id}");
}
$problem = $case["problem"];
$solver = $case["solver"];
$mesher = $case["mesher"];

$cad_dir = "../data/{$owner}/cads/{$case["cad"]}";
if (is_dir("{$cad_dir}/meshes") == false) {
  mkdir("{$cad_dir}/meshes", $permissions, true);
}

if (is_dir("{$case_dir}/run") == false) {
  mkdir("{$case_dir}/run", $permissions, true);
}
if (file_exists("{$case_dir}/run/meshes") == false) {
  symlink("../../../cads/{$case["cad"]}/meshes", "../data/{$owner}/cases/{$id}/run/meshes");
}

include("../solvers/{$solver}/common.php");

if ($mesh_hash == "") {
  update_mesh_in_fee();
  $mesh_hash = mesh_hash();
}
if ($problem_hash == "") {
  $problem_hash = problem_hash();
}

$mesh_path = "{$cad_dir}/meshes/{$mesh_hash}.msh";
$mesh_data_path = "{$cad_dir}/meshes/{$mesh_hash}-data.json";
$mesh_meta_path = "{$cad_dir}/meshes/{$mesh_hash}.json";

$has_mesh = file_exists($mesh_path);
$has_mesh_valid = false;
$has_mesh_attempt = file_exists($mesh_meta_path);
if ($has_mesh_attempt && ($mesh_meta = json_decode(file_get_contents($mesh_meta_path), true)) != null) {
  if ($mesh_meta["status"] == "success" && ($mesh_meta["nodes"] <= $max_nodes)) {
    $has_mesh_valid = true;
  } else if ($mesh_meta["status"] == "running") {
    if (isset($mesh_meta["pid"]) == false || posix_getpgid($mesh_meta["pid"]) == false) {
      // TODO: look at the .2
      $mesh_meta["status"] = "error";
      // TODO: update json
    } else {
      // TODO: calculate progress
    }
  }
}




$results_meta_path = "{$case_dir}/run/{$problem_hash}.json";

// TODO: per-problem data
$results_data_path = "{$case_dir}/run/{$problem_hash}-{$primary_field[$problem]}.dat";
$has_results = file_exists($results_data_path);
$has_results_attempt = file_exists($results_meta_path);
if ($has_results_attempt && ($results_meta = json_decode(file_get_contents($results_meta_path), true)) != null) {
  if ($results_meta["status"] == "success") {
    $has_results_valid = true;
  } else if ($results_meta["status"] == "running") {
    if (isset($results_meta["pid"]) && posix_getpgid($results_meta["pid"]) == false) {
      // TODO: mirar el .2
      $results_meta["status"] = "error";
      // TODO: update json
    } else {
      // TODO: calcular progress
    }
  }
}
?>
