<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

include("common.php");

$what = isset($_GET["what"]) ? $_GET["what"] : "physics";

if (file_exists("../../data") === false) {
  mkdir("../../data", $permissions, true);
}
$log = fopen("../../data/problems.log", "a");
if ($log === false) {
  echo "Cannot open data directory, please check permissions.";
  exit(1);
}
fprintf($log, "%s\t%s\t%s\n", date("c"), $_SERVER['REMOTE_ADDR'], $what);
fclose($log);

if ($what == "physics") {

//  $keys["none"] = "";
  foreach ($problems as $physics => $problem) {
    $keys[$physics] = $physics_name[$physics];
  }

} else if (isset($problems[$what])) {

//  $keys["none"] = "";
  foreach ($problems[$what] as $index => $problem) {
    $keys[$problem] = $problem_name[$problem];
  }

} else if (isset($solvers[$what])) {

  foreach ($solvers[$what] as $index => $solver) {
    $keys[$solver] = $solvers_names[$solver];
  }
} else {
  // TODO:
  $keys["gmsh"] = "Gmsh";

/*  
} else if ($what == "fluid") {

  $keys = ["none", "fluid-irrotational", "fluid-incompressible", "fluid-compressible"];
  $values = ["", "Laminar irrotational flow", "Incompressible flow", "Compressible flow"];
  
} else if ($what == "thermal") {

  $keys = ["none", "conduction", "radiation"];
  $values = ["", "Heat conduction", "Radiative heat exchange"];

} else if ($what == "electromagnetism") {

  $keys = ["none", "electrostatics", "motor", "antenna"];
  $values = ["", "Electrostatics", "Electrical motors", "Antenna radiation"];
  
} else if ($what == "acoustics") {

  $keys = ["none", "acoustics-linear", "ultrasonics"];
  $values = ["", "Linear acoustics", "Ultrasonics"];
  
} else if ($what == "neutron") {

  $keys = ["none", "neutron-diffusion", "neutron-sn"];
  $values = ["", "Multigroup diffusion", "Multigroup SN"];

} else if ($what == "multiphysics") {

  $keys = ["none", "thermo-mechanical", "conjugate-heat", "fluid-structure"];
  $values = ["", "Thermomechanical", "Conjugate heat transfer", "Fluid-structure interaction"];
*/  
}  

$response["keys"] = array();
$response["values"] = array();

foreach ($keys as $key => $value) {
  array_push($response["keys"], $key);
  array_push($response["values"], $value);
}

return_back_json($response);
