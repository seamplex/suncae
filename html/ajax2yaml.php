<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

include("../conf.php");
include("../auths/{$auth}/auth.php");
include("common.php");
include("case.php");

$response["mesh_hash"] = $mesh_hash;
$response["problem_hash"] = $problem_hash;
$response["error"] = "";
$response["warning"] = "";
$response["content_id"] = array();
$response["content_html"] = array();
$response["block"] = array();
$response["inline"] = array();
$response["hide"] = array();

$update_yaml = false;
$field = $_GET["field"];
$value = $_GET["value"];

if (chdir("../data/{$owner}/cases/{$id}") === false) {
  return_error_json("cannot chdir to user dir {$id}");
}


// ---- case properties ----------------------------
// TODO: validate fields
if ($field == "name" ||
    $field == "visibility") {

  if ($field == "name") {
    array_push($response["content_id"], "span_name");
    array_push($response["content_html"], $value);
  }
  if ($field == "visibility") {
    if ($value == "public") {
      array_push($response["inline"], "i_public");
    } else {
      array_push($response["hide"], "i_public");
    }
  }

  $case[$field] = $value;
  $update_yaml = true;
}
if ($update_yaml) {
  file_put_contents("case.yaml", yaml_emit($case));
}

exec("git commit -a -m 'case {$field} = {$value}'", $output, $result);
if ($result != 0) {
  suncae_log_error("cannot git commit {$problem} {$id}");
  echo "cannot git commit {$case["problem"]} {$id}";
  exit(1);
}
suncae_log("case {$id} ajax2yaml {$field} = {$value}", 0);


// TODO: git commit
return_back_json($response);
