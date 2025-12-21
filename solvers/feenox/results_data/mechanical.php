<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

// TODO: read & check
$displacements_data_path = "../data/{$username}/cases/{$id}/run/{$problem_hash}-displacements.dat";
$sigma_data_path = "../data/{$username}/cases/{$id}/run/{$problem_hash}-sigma.dat";

if (file_exists($displacements_data_path) == false) {
  $response["error"] = "Displacements data path does not exist";
  suncae_log_error("case {$id} failed \"{$response["error"]}\"");
  return_back_json($response);
}
if (file_exists($sigma_data_path) == false) {
  $response["error"] = "Stress data path does not exist";
  suncae_log_error("case {$id} failed \"{$response["error"]}\"");
  return_back_json($response);
}

$response["nodes_warped"] = file_get_contents($displacements_data_path);
$response["field"]        = file_get_contents($sigma_data_path);

return_back_json($response);
