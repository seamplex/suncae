<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

chdir("../data/{$username}/cases/{$id}/");
$response["plain"] = file_get_contents("case.fee");
$response["html"] = shell_exec("cat << EOF | ../../../../bin/pandoc -t html --syntax-definition=../../../../solvers/feenox/feenox.xml
~~~feenox
$(cat case.fee)
~~~
EOF");

return_back_json($response);
