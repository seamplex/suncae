<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

// TODO: read & check
$temperature_data_path = "../data/{$owner}/cases/{$id}/run/{$problem_hash}-T.dat";
if (file_exists($temperature_data_path) == false) {
  $response["error"] = "Temperature data path does not exist";
  suncae_log_error("case {$id} failed \"{$response["error"]}\"");
  return_back_json($response);
}

$response["field"] = file_get_contents($temperature_data_path);

return_back_json($response);
