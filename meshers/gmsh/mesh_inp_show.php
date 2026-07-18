<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

chdir("../data/{$owner}/cases/{$id}/");
$response["plain"] = "";
foreach (file("mesh.geo", FILE_IGNORE_NEW_LINES) as $line) {
	if (stripos($line, "merge") === false) {
		$response["plain"] .= $line . "\n";
	}
}
$mesh_input = "Merge \"../../cads/{$case["cad"]}/cad.xao\";\n" . $response["plain"];
$response["html"] = "<pre><code>" . htmlspecialchars($mesh_input) . "</code></pre>";

return_back_json($response);
