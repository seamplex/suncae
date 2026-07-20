<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

if ($mesh_hash == "") {
	return_error_json("missing mesh hash");
}
$error = suncae_delete_hashed_artifacts("../data/{$owner}/cases/{$id}/run/meshes", $mesh_hash, [".json", "-status.json", ".msh", ".1", ".2", "-data.log", "-data.json", ".intersections", ".gp", ".png", "-2.msh", "-2.1"], "mesh");
if ($error != "") {
	return_error_json($error);
	exit();
}
$result["status"] = "ok";
return_back_json($result);
