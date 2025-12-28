<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

chdir("../data/{$owner}/cases/{$id}");
$fee = fopen("case.fee", "w");
fprintf($fee, "PROBLEM {$problem_name[$problem]} MESH {$mesh_hash}%s.msh\n", ($mesh_order[$problem] == 1) ? "" : $mesh_order[$problem]);
fwrite($fee, $_POST["fee"]);
fclose($fee);

$response["status"] = "ok";
$response["error"] = "";

// TODO: put this in a function and call it from ajax2yaml
// validate .fee with feenox
exec("../../../../bin/feenox -c case.fee 2>&1", $output, $result);
if ($result != 0) {
  $response["status"] = "error";
  for ($i = 0; $i < count($output); $i++) {
    // this authorization comes from openmpi
    if ($output[$i] != "" && strncasecmp($output[$i], "Authorization", 13) != 0) {
      if (strncmp("error", $output[$i], 5) == 0) {
        $output_exploded = explode(":", $output[$i]);
        for ($j = 2; $j < count($output_exploded); $j++) {
          $response["error"] .= $output_exploded[$j] ;
        }
        $response["error"] .= "<br>";
      } else {
        $response["error"] .= $output[$i] . "<br>";
      }
    }
  }
}

if ($response["error"] != "") {
  suncae_log_error("case {$id} fee save failed: {$response["error"]}");
}

return_back_json($response);
