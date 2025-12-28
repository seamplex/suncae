<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

$mesh_hash = $_GET["mesh_hash"];

$response["stdout"] = file_get_contents("../data/{$owner}/cases/{$id}/run/meshes/{$mesh_hash}.1");
$response["stderr"] = file_get_contents("../data/{$owner}/cases/{$id}/run/meshes/{$mesh_hash}.2");

return_back_json($response);
