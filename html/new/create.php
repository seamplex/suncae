<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

include("../../conf.php");
include("../../auths/{$auth}/auth.php");
include("../common.php");
include("../../solvers/common.php");

suncae_require_post_csrf();

$problem = isset($_POST["problem"]) ? suncae_require_path_component($_POST["problem"], "problem") : suncae_error("missing problem");
$mesher = isset($_POST["mesher"]) ? suncae_require_path_component($_POST["mesher"], "mesher") : suncae_error("missing mesher");
$solver = isset($_POST["solver"]) ? suncae_require_path_component($_POST["solver"], "solver") : suncae_error("missing solver");
$owner = suncae_require_path_component($username, "owner");
if (!isset($solvers[$problem]) || !in_array($solver, $solvers[$problem], true)) {
  suncae_error("invalid solver for problem");
}
if (!isset($meshers[$solver]) || !in_array($mesher, $meshers[$solver], true)) {
  suncae_error("invalid mesher for solver");
}
include("../../solvers/{$solver}/input_initial_{$problem}.php");



if (file_exists("../../data/{$owner}/cases") ==  false) {
  if (mkdir("../../data/{$owner}/cases", 0755, true) == false) {
    suncae_error("error: cannot create cases directory");
  }
}
if (chdir("../../data/{$owner}/cases") == false) {
  suncae_error("error: cannot chdir to cases");
}

$cad = isset($_POST["cad_hash"]) ? suncae_require_hash($_POST["cad_hash"], "cad hash") : suncae_error("missing cad hash");
$id = md5((`which uuidgen`) ? shell_exec("uuidgen") : uniqid());

if (file_exists($id) === true) {
  suncae_error("project {$id} already exists");
}
if (mkdir($id, 0775, true)) {
  suncae_error("cannot create case {$id} directory");
}
chdir($id);

// TODO: per mesher
copy("../../cads/{$cad}/default.geo", "mesh.geo");

$case["id"] = $id;
$case["owner"] = $owner;
$case["date"] = time();
$case["cad"] = $cad;
$case["problem"] = $problem;
$case["mesher"] = $mesher;
$case["solver"] = $solver;
$case["name"] = isset($_POST["name"]) ? $_POST["name"] : "Unnamed";
$case["visibility"] = "public";
yaml_emit_file("case.yaml", $case);

solver_input_write_initial("case.fee", $case["problem"]);

$gitignore = fopen(".gitignore", "w");
fprintf($gitignore, "run\n");
fclose($gitignore);

# TODO: create a local user
// exec("git init --initial-branch=main", $output, $result);
exec("git init", $output, $result);
if ($result != 0) {
  return_error_json("cannot git init {$case["problem"]} {$id}");
}

exec("git config user.name " . escapeshellarg($owner), $output, $result);
if ($result != 0) {
  return_error_json("cannot set user.name {$case["problem"]} {$id}");
}

exec("git config user.email " . escapeshellarg("{$owner}@suncae"), $output, $result);
if ($result != 0) {
  return_error_json("cannot set user.email {$case["problem"]} {$id}");
}

exec("git add .", $output, $result);
if ($result != 0) {
  return_error_json("cannot git add {$case["problem"]} {$id}");
}
exec("git commit -m 'initial commit'", $output, $result);
if ($result != 0) {
  return_error_json("cannot git commit {$case["problem"]} {$id}");
}

suncae_log("created case {$id} type {$case["problem"]} ", 2);

header("Location: ../?id={$id}");
