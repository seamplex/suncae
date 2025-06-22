<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

$response["error"] = "";
$response["warning"] = "";

$field = $_GET["field"];
$value = $_GET["value"];

if (chdir("../data/{$username}/cases/{$id}") === false) {
  return_error_json("cannot chdir to user dir {$id}");
}

// ---- case.fee ----------------------------
// first we update the material properties & bcs
// TODO: per-physics
if ($field == "PC" ||
    $field == "E" ||
    $field == "nu" ||
    $field == "k" ||
    $field == "q" ||
    strncmp($field, "bc_", 3) == 0) {

  $bc_n = 0;
  if (strncmp($field, "bc_", 3) == 0) {
    $bc_exploded = explode("_", $field);
    $bc_n = intval($bc_exploded[1]);
    $bc_field = $bc_exploded[2];
  }

  // TODO: lock
  $current = fopen("case.fee", "r");
  $new = fopen("new.fee", "w");
  $bc_i = 1;
  $written_bc = false;
  $written_pc = false;
  if ($current && $new) {
    while (($line = fgets($current)) !== false) {
      if ($field == "PC" && strncmp("PROBLEM", $line, 7) == 0) {
        if ($written_pc == false) {
          if (strncmp("PROBLEM PC", $line, 10) != 0) {
            fprintf($new, "%s", $line);
          }
          fprintf($new, "PROBLEM PC %s\n\n", $value);
          $written_pc = true;
        }

      } else if ($field == "E" && strncmp("E(x,y,z) = ", $line, 11) == 0) {
        // TODO: see if the value is a constant and use E = in that case
        // TODO: see if it is a constant, expression, etc.
        // if it is 2000000000 then replace it with 2e9
        if (strpos($value, ",") !== false) {
          $response["warning"] = "Note that the decimal separator is dot, not comma.";
        }
        fprintf($new, "E(x,y,z) = (%s)*1e3\n", $value);

      } else if ($field == "nu" &&  strncmp("nu = ", $line, 5) == 0) {
        if (strpos($value, ",") !== false) {
          $response["warning"] = "Note that the decimal separator is dot, not comma.";
        }
        fprintf($new, "nu = %s\n", $value);

      } else if ($field == "k" &&  strncmp("k(x,y,z) = ", $line, 11) == 0) {
        if (strpos($value, ",") !== false) {
          $response["warning"] = "Note that the decimal separator is dot, not comma.";
        }
        fprintf($new, "k(x,y,z) = (%s)*1e-3\n", $value);

      } else if ($field == "q" &&  strncmp("q(x,y,z) = ", $line, 11) == 0) {
        if (strpos($value, ",") !== false) {
          $response["warning"] = "Note that the decimal separator is dot, not comma.";
        }
        fprintf($new, "q(x,y,z) = %s\n", $value);

      } else if (strncmp("BC ", $line, 3) == 0 || strncmp("BC\t", $line, 3) == 0) {
        
        // let's parse the existing BC
        $bc_group = array();
        $bc_value = array();
        $n_values = 0;
        $n_groups = 0;
        $line_exploded = explode(" ", rtrim($line));
        $bc_name = $line_exploded[1];
        $i = 2;
        $explicit_group = false;
        while (isset($line_exploded[$i])) {
          if ($line_exploded[$i] == "GROUPS") {
            $explicit_group = true;
            while (isset($line_exploded[$i+1])) {
              $i++;
              sscanf($line_exploded[$i], "%s", $bc_group[$n_groups++]);
            }
            break;
          } else if ($line_exploded[$i] == "GROUP") {
            $explicit_group = true;
            $i++;
            sscanf($line_exploded[$i], "%s", $bc_group[$n_groups++]);
          } else {
            $bc_value[$n_values++] = $line_exploded[$i];
          }
          $i++;
        }

        if ($bc_n == $bc_i) {
          if ($explicit_group == true && $n_groups == 0) {
            $bc_group[0] = $bc_name;
            $n_groups = 1;
          }

          if ($bc_field == "face" || $bc_field == "edge") {
            $bc_group = array();
            if ($value != "") {
              $faces = explode(",", $value);
              $n_groups = 0;
              foreach ($faces as $face) {
                $bc_group[$n_groups++] = sprintf("%s%d", $bc_field, $face);
              }
            }
          } else if ($bc_field == "value") {
            $bc_value = array();
            $values = explode(" ", $value);
            $n_values = 0;
            foreach ($values as $val) {
              $bc_value[$n_values++] = $val;
            }
          }
          $written_bc = true;
        }

        $bc_name = sprintf("bc%d", $bc_i);
        if ((isset($bc_field) && $bc_field != "remove" && $value != "") || $bc_n != $bc_i) {
          // print the new BC
          fprintf($new, "BC %s", $bc_name);
          foreach ($bc_value as $val) {
            fprintf($new, " %s", $val);
          }
          fprintf($new, " GROUPS");
          for ($group_i = 0; $group_i < $n_groups; $group_i++) {
            fprintf($new, " %s", $bc_group[$group_i]);
          }
          fprintf($new, "\n");
        }
        $bc_i++;
      } else if (strncmp("SOLVE_PROBLEM", $line, 13) == 0) {
        if (isset($bc_field) && $bc_field != "" && $written_bc == false) {
          $bc_name = sprintf("bc%d", $bc_n);
          $bc_group = array();
          $bc_value = array();
          $n_groups = 0;
          $n_values = 0;
          // add a new BC
          if ($bc_field == "face" || $bc_field == "edge") {
            $faces = explode(",", $value);
            foreach ($faces as $face) {
              $bc_group[$n_groups++] = $face;
            }
          } else if ($bc_field == "value") {
            $values = explode(" ", $value);
            foreach ($values as $val) {
              $bc_value[$n_values++] = $val;
            }
          }
          if ($n_values == 0) {
            $bc_value[0] = $default_bc[$problem];
            $n_values = 1;
          }

          // print the new BC
          fprintf($new, "BC %s", $bc_name);
          foreach ($bc_value as $val) {
            fprintf($new, " %s", $val);
          }
          fprintf($new, " GROUPS");
          foreach ($bc_group as $group) {
            fprintf($new, " %s%d", $bc_field, $group);
          }
          fprintf($new, "\n");
        }
        fprintf($new, "\n");
        fwrite($new, $line);
      } else {
        // only print non-empty lines
        if (trim($line) != "") {
          fwrite($new, $line);
        }
      }
    }
    fclose($current);
    fclose($new);

    if (rename("new.fee", "case.fee") !== true) {
      return_error_json("Cannot update fee");
    }

    // TODO: solver-dependent
    // validate .fee with feenox
    exec("../../../../bin/feenox -c case.fee 2>&1", $output, $result);
    if ($result != 0) {
      for ($i = 0; $i < count($output); $i++) {
        // this authorization comes from openmpi
        if ($output[$i] != "" && strncasecmp($output[$i], "Authorization", 13) != 0) {
          if (strncmp("error", $output[$i], 5) == 0) {
            $output_exploded = explode(":", $output[$i]);
            for ($j = 3; $j < count($output_exploded); $j++) {
              $response["error"] .= $output_exploded[$j] ;
            }
            $response["error"] .= "<br>";
          } else {
            $response["error"] .= $output[$i] . "<br>";
          }
        }
      }
    }

  } else {
    return_error_json("cannot open case.fee");
  }
}

// see if there's something to commit
exec("git status --porcelain", $output, $result);
if (count($output) > 0) {
  exec("git commit -a -m 'problem {$field} = {$value}'", $output, $result);
  if ($result != 0) {
    return_error_json("cannot git commit {$id}: {$output[0]} {$output[1]}");
  }
}
suncae_log("problem {$id} ajax2problem {$field} = {$value}");
if ($response["error"] != "") {
  suncae_log("case {$id} error: {$response["error"]}");
}

return_back_json($response);
