<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

chdir("../data/{$owner}/cases/{$id}/");
$fee = file("case.fee", FILE_IGNORE_NEW_LINES);
$response["header"] = isset($fee[0]) ? htmlspecialchars($fee[0]) . "<br>" : "";
$response["plain"] = implode("\n", array_slice($fee, 1));
$response["html"] = "<pre><code>" . htmlspecialchars(implode("\n", $fee)) . "</code></pre>";

return_back_json($response);
