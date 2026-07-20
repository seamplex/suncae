<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

include("../conf.php");
include("../auths/{$auth}/auth.php");
include("common.php");
include("case.php");

$hash = isset($_GET["hash"]) ? suncae_require_hash($_GET["hash"], "mesh hash") : suncae_error("missing mesh hash");

$msh_path = "{$case_dir}/run/meshes/{$hash}.msh";
if (file_exists($msh_path)) {
  header("Content-Description: File Transfer");
  header("Content-Type: application/octet-stream");
  header("Content-Disposition: attachment; filename={$hash}.msh");
  header("Content-Length: " . filesize($msh_path));
  ob_clean();
  flush();
  readfile($msh_path);
}
