<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

chdir("../data/{$username}/cases/{$id}");
$geo = fopen("mesh.geo", "w");
fwrite($geo, "Merge \"../../cads/{$case["cad"]}/cad.xao\";\n");
fwrite($geo, $_POST["geo"]);
fclose($geo);

$response["status"] = "ok";
$response["error"] = "";

// TODO: put this in a function and call it from change_step as well
exec("../../../../bin/gmsh -check mesh.geo 2>&1", $output, $result);
if ($result != 0) {
  $response["status"] = "error";
  for ($i = 0; $i < count($output); $i++) {
    if (strncmp("Error", $output[$i], 5) == 0) {
      $response["error"] .= $output[$i] . "<br>";
    } 
  }
}


if ($response["error"] != "") {
  suncae_log_error("csae {$id} mesh save failed \"{$response["error"]}\"");
}
return_back_json($response);
