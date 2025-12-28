<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

chdir("../data/{$owner}/cases/{$id}/");
$response["plain"] = shell_exec("grep -vi merge mesh.geo");
$response["html"] = shell_exec("cat << EOF | ../../../../bin/pandoc -t html
~~~c
Merge \"../../cads/{$case["cad"]}/cad.xao\";
{$response["plain"]}
~~~
EOF");

return_back_json($response);
