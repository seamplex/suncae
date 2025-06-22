<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.


// TODO: classes in a header which we can include
$mesh_order["mechanical"] = 2;
$problem_name["mechanical"] = "mechanical";
$primary_field["mechanical"] = "displacements";
$default_bc["mechanical"] = "fixed";

function problem_hash() {
  global $username, $id;
  if (file_exists("case.fee")) {
    return md5_file("case.fee");
  } else {
    return md5_file("../data/{$username}/cases/{$id}/case.fee");
  }
}

function update_mesh_in_fee() {
  global $username;
  global $id;
  global $mesh_hash;
  global $problem;
  global $mesh_order;
  $real_mesh_hash = mesh_hash();
  if ($real_mesh_hash != $mesh_hash) {
    if ($mesh_order[$problem] == 1) {
      shell_exec("sed -i s/{$mesh_hash}.msh/{$real_mesh_hash}\.msh/ ../data/{$username}/cases/{$id}/case.fee");
    } else {
      shell_exec("sed -i s/{$mesh_hash}.msh/{$real_mesh_hash}{$mesh_order[$problem]}\.msh/ ../data/{$username}/cases/{$id}/case.fee");
    }
  }
}


?>
