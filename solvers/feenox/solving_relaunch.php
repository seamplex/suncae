<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

if ($problem_hash == "") {
	return_error_json("missing problem hash");
}
$error = suncae_delete_hashed_artifacts("../data/{$owner}/cases/{$id}/run", $problem_hash, [".json", "-status.json", "-solve.log", "-check.2", ".fee", ".inp", ".1", ".2", ".vtk", "-1.vtk", "-max.json", "-displacements.dat", "-sigma.dat", "-T.dat"], "solve");
if ($error != "") {
	return_error_json($error);
	exit();
}
$result["status"] = "ok";
return_back_json($result);
