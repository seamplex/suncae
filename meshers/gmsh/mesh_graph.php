<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

$id = $_GET["id"];
$file = $_GET["file"];

$svg_path = "../data/{$owner}/cases/{$id}/run/meshes/{$file}.svg";
if (file_exists($svg_path)) {

  header("Content-Type: image/svg+xml");
  header("Content-Length: " . filesize($svg_path));

  ob_clean();
  flush();
  readfile($svg_path);
  flush();
  
} else {

  // TODO: devolver un dummy
  exit();
}
