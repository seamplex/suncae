<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

$step_url[-5] = "history.php";
$step_url[-4] = "properties.php";

$step_url[-1] = "meshing.php";
$step_url[1] = "mesh.php";

$step_url[-2] = "expert.php";
$step_url[2] = "problem.php";

$step_url[-3] = "solving.php";
$step_url[3] = "results.php";

$step_url[+4] = "expert.php";
$step_url[+5] = "share.php";


if (!isset($_POST["next_step"]) || $_POST["next_step"] < -5 || $_POST["next_step"] > 5) {
  return_error_json("Invalid step");
}
$next_step = $_POST["next_step"];
$current_step = (isset($_POST["current_step"])) ? $_POST["current_step"] : 2;

// if the user is coming from mesh but the mesh options has changed, we have to re-mesh and go back
if ($current_step == 1 && $has_mesh_attempt == false) {
  $next_step = 1;
}

switch ($next_step) {
  case 1:
    // TODO: per-mesher
    chdir($case_dir);
    if (file_exists("mesh.geo") == false) {
      $geo = fopen("mesh.geo", "w");
      fprintf($geo, "Merge \"../../cads/{$case["cad"]}/cad.xao\";\n");
      fclose($geo);
    }
    
    $response["mesh"] = ($has_mesh) ? $mesh_hash : "";
    if ($has_mesh_attempt == false) {
      exec("../../../../bin/gmsh -check mesh.geo 1> run/meshes/{$mesh_hash}-check.1 2> run/meshes/{$mesh_hash}-check.2", $output, $result);
      if ($result == 0) {
        // https://www.php.net/manual/en/function.exec.php
        // https://stackoverflow.com/questions/45953/php-execute-a-background-process
        $pid = suncae_local_job_start("../../../../meshers/gmsh/mesh.sh", "run/meshing.log", "run/meshing.pid", $output, $result);
        if ($pid > 0) {
          $mesh_meta["status"] = "running";
          $mesh_meta["pid"] = $pid;
          $mesh_meta["started_at"] = date("c");
          suncae_log("{$id} mesh running pid {$pid}");
        } else {
          $mesh_meta["status"] = "error";
          suncae_log_error("{$id} cannot launch mesh job");
        }
      } else {
        $mesh_meta["status"] = "syntax_error";
        suncae_log("{$id} mesh syntax error");
      }
      suncae_write_json_file("run/meshes/{$mesh_hash}.json", $mesh_meta);
    }
    // if running, go to meshing.php otherwise go to mesh.php, it will know what to show}
    // TODO: AND or OR?
    $next_step *= (isset($mesh_meta["status"]) && $mesh_meta["status"] != "running") ? (+1) : (-1);
    
    suncae_log("{$id} change_step {$current_step} -> {$next_step}");
    
  break;
  
  case 3:
    $response["results"] = ($has_results) ? $problem_hash : "";
    $response["has_results_attempt"] = $has_results_attempt;
    if ($has_results_attempt == false) {
      
      include("../solvers/{$solver}/change_step_solve.php");
      suncae_write_json_file("run/{$problem_hash}.json", $results_meta);
      suncae_log("{$id} change_step {$current_step} -> {$next_step}");
      
    }
    if (isset($results_meta)) {
      $next_step *= ($results_meta["status"] != "running") ? (+1) : (-1);
    }
  break;
}

$response["step" ] = $next_step;
$response["url"] = "{$step_url[$next_step]}?id={$id}";


return_back_json($response);
