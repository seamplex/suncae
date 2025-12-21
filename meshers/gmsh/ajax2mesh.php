<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

$response["mesh_hash"] = $mesh_hash;
$response["error"] = "";
$response["warning"] = "";

$field = $_GET["field"];
$value = $_GET["value"];

if (chdir("../data/{$username}/cases/{$id}") === false) {
  return_error_json("cannot chdir to user dir {$id}");
}

// ---- mesh.geo --------------------------------------------
// TODO: lock
$current = fopen("mesh.geo", "r");
$new = fopen("new.geo", "w");
// $mesh_i = 1;
// $validate = true;
$written = false;
// TODO: remove
$remove = ($value == "remove");
if ($current && $new) {
  while (($line = fgets($current)) !== false) {
    if (strncmp("Merge ", $line, 6) == 0) {
      fprintf($new, "Merge \"../../cads/%s/cad.xao\";\n", $case["cad"]);

    } else if (strncmp("Mesh.", $line, 5) == 0) {
      preg_match('/Mesh.(.*) = (.*);/', $line, $matches);
      if ($field == $matches[1]) {
        if ($remove == false) {
          fprintf($new, "Mesh.%s = %s;\n", $field, $value);
        }
        $written = true;
      } else {
        fwrite($new, $line);
      }
    } else if (str_contains($line, "SystemCall") === false) {
      fwrite($new, $line);
    }
  }
      
  // if it was a new addition, put it at the end
  if ($written == false && $remove == false ) {
    fprintf($new, "Mesh.%s = %s;\n", $field, $value);
  }
    
  fclose($current);
  fclose($new);

  if (rename("new.geo", "mesh.geo") !== true) {
    return_error_json("Cannot update geo");
  }

  // validate .geo with gmsh
  exec("../../../../bin/gmsh -check mesh.geo 2>&1", $output, $result);
  if ($result != 0) {
    for ($i = 0; $i < count($output); $i++) {
      if (strncmp("Error", $output[$i], 5) == 0) {
        $output_exploded = explode(":", $output[$i]);
        for ($j = 1; $j < count($output_exploded); $j++) {
          $response["error"] .= $output_exploded[$j] ;
        }
        $response["error"] .= "<br>";
      } 
    }
  }

} else {
  return_error_json("cannot open mesh.geo");
}
$mesh_hash = mesh_hash();

exec("git commit -a -m 'mesh {$field} = {$value}'", $output, $result);
if ($result != 0) {
  return_error_json("cannot git commit {$id}: {$output[0]}");
}
suncae_log("mesh {$id} ajax2yaml {$field} = {$value}");

if ($response["error"] != "") {
  suncae_log_error("mesh {$id} failed: {$response["error"]}");
}

return_back_json($response);
