<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

$response["error"] = "";
$response["warning"] = "";

$field = isset($_POST["field"]) ? suncae_require_field_name($_POST["field"], "problem field") : suncae_error("missing problem field");
$value = isset($_POST["value"]) ? suncae_require_single_line($_POST["value"], "problem value") : suncae_error("missing problem value");

if (chdir("../data/{$owner}/cases/{$id}") === false) {
  return_error_json("cannot chdir to user dir {$id}");
}

function suncae_material_token_quote($token) {
  if (preg_match('/\s/', $token) === 1) {
    return '"' . str_replace('"', '\\"', $token) . '"';
  }
  return $token;
}

function suncae_material_parse_line($line) {
  $trimmed = trim($line);
  if (preg_match('/^MATERIAL\s+([A-Za-z0-9_.-]+)\s*(.*)$/', $trimmed, $matches) !== 1) {
    return null;
  }
  $label = $matches[1];
  $rest = isset($matches[2]) ? $matches[2] : "";
  $tokens = array_values(array_filter(str_getcsv($rest, ' ', '"'), function($token) {
    return $token !== "";
  }));

  $properties = array();
  $other_tokens = array();
  foreach ($tokens as $token) {
    if (preg_match('/^(E|nu|k|q)=(.+)$/', $token, $property_match) === 1) {
      $properties[$property_match[1]] = $property_match[2];
    } else {
      $other_tokens[] = $token;
    }
  }

  return array(
    "label" => $label,
    "properties" => $properties,
    "other_tokens" => $other_tokens
  );
}

function suncae_material_write_line($label, $tokens) {
  if (count($tokens) == 0) {
    return "";
  }
  $quoted = array();
  foreach ($tokens as $token) {
    $quoted[] = suncae_material_token_quote($token);
  }
  return "MATERIAL {$label} " . implode(" ", $quoted);
}

function suncae_material_parse_function_line($line) {
  if (preg_match('/^(E|nu|k|q)_([A-Za-z0-9_.-]+)\s*\(x,y,z\)\s*=\s*(.+)$/', trim($line), $matches) === 1) {
    return array(
      "property" => $matches[1],
      "label" => $matches[2],
      "expression" => trim($matches[3])
    );
  }
  return null;
}

$is_material_field = preg_match('/^mat_([A-Za-z0-9_.-]+)_(E|nu|k|q)$/', $field, $material_field_match) === 1;

// ---- case.fee ----------------------------
// first we update the material properties & bcs
// TODO: per-physics
if ($field == "PC" ||
    $field == "E" ||
    $field == "nu" ||
    $field == "k" ||
    $field == "q" ||
    $is_material_field ||
    strncmp($field, "bc_", 3) == 0) {

  if ($is_material_field) {
    $material_label = $material_field_match[1];
    $material_property = $material_field_match[2];
    if (strpos($value, ",") !== false) {
      $response["warning"] = "Note that the decimal separator is dot, not comma.";
    }

    $fee_lines = file("case.fee", FILE_IGNORE_NEW_LINES);
    if ($fee_lines === false) {
      return_error_json("cannot open case.fee");
    }

    $label_properties = array();
    foreach ($fee_lines as $line) {
      $function_data = suncae_material_parse_function_line($line);
      if ($function_data !== null) {
        $label = $function_data["label"];
        if (!isset($label_properties[$label])) {
          $label_properties[$label] = array();
        }
        $label_properties[$label][$function_data["property"]] = $function_data["expression"];
        continue;
      }

      $material_data = suncae_material_parse_line($line);
      if ($material_data !== null) {
        $label = $material_data["label"];
        if (!isset($label_properties[$label])) {
          $label_properties[$label] = array();
        }
        foreach ($material_data["properties"] as $property_name => $property_expression) {
          $label_properties[$label][$property_name] = $property_expression;
        }
      }
    }

    if (!isset($label_properties[$material_label])) {
      $label_properties[$material_label] = array();
    }
    if (trim($value) == "") {
      unset($label_properties[$material_label][$material_property]);
    } else {
      $trimmed_value = trim($value);
      if ($material_property == "E") {
        // Keep UI values in GPa and store in fee expressions using MPa units.
        $label_properties[$material_label][$material_property] = "(" . $trimmed_value . ")*1e3";
      } else {
        $label_properties[$material_label][$material_property] = $trimmed_value;
      }
    }

    $material_lines = array();
    ksort($label_properties, SORT_NATURAL);
    foreach ($label_properties as $label => $properties) {
      $tokens = array();
      if (isset($properties["E"]) && trim($properties["E"]) != "") {
        $tokens[] = "E=" . $properties["E"];
      }
      if (isset($properties["nu"]) && trim($properties["nu"]) != "") {
        $tokens[] = "nu=" . $properties["nu"];
      }
      if (isset($properties["k"]) && trim($properties["k"]) != "") {
        $tokens[] = "k=" . $properties["k"];
      }
      if (isset($properties["q"]) && trim($properties["q"]) != "") {
        $tokens[] = "q=" . $properties["q"];
      }
      $line = suncae_material_write_line($label, $tokens);
      if ($line != "") {
        $material_lines[] = $line;
      }
    }

    $new_lines = array();
    $inserted_materials = false;
    foreach ($fee_lines as $line) {
      if (suncae_material_parse_function_line($line) !== null) {
        continue;
      }

      $material_data = suncae_material_parse_line($line);
      if ($material_data !== null) {
        if (count($material_data["other_tokens"]) > 0) {
          $rebuilt = suncae_material_write_line($material_data["label"], $material_data["other_tokens"]);
          if ($rebuilt != "") {
            $new_lines[] = $rebuilt;
          }
        }
        continue;
      }

      if ($inserted_materials == false && preg_match('/^\s*SOLVE_PROBLEM\b/', $line) === 1) {
        foreach ($material_lines as $material_line) {
          $new_lines[] = $material_line;
        }
        if (count($material_lines) > 0) {
          $new_lines[] = "";
        }
        $inserted_materials = true;
      }

      if (trim($line) != "") {
        $new_lines[] = $line;
      }
    }

    if ($inserted_materials == false && count($material_lines) > 0) {
      foreach ($material_lines as $material_line) {
        $new_lines[] = $material_line;
      }
      $new_lines[] = "";
    }

    $new_content = implode("\n", $new_lines) . "\n";
    if (file_put_contents("new.fee", $new_content) === false || rename("new.fee", "case.fee") !== true) {
      return_error_json("Cannot update fee");
    }

    exec(suncae_with_runtime_env("../../../../bin/feenox -c case.fee 2>&1"), $output, $result);
    if ($result != 0) {
      for ($i = 0; $i < count($output); $i++) {
        if ($output[$i] != "" && strncasecmp($output[$i], "Authorization", 13) != 0) {
          if (strncmp("error", $output[$i], 5) == 0) {
            $output_exploded = explode(":", $output[$i]);
            for ($j = 3; $j < count($output_exploded); $j++) {
              $response["error"] .= $output_exploded[$j];
            }
            $response["error"] .= "<br>";
          } else {
            $response["error"] .= $output[$i] . "<br>";
          }
        }
      }
    }

    if ($response["error"] != "") {
      suncae_log_error("case {$id} ajax2problem failed: {$response["error"]}");
    }
  } else {

  if (strncmp($field, "bc_", 3) == 0 && preg_match('/^bc_[0-9]+_(face|edge|value|remove)$/', $field) !== 1) {
    return_error_json("invalid boundary-condition field");
  }

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
          if ($value != "default") {
            fprintf($new, "PROBLEM PC %s\n\n", $value);
          }
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
    exec(suncae_with_runtime_env("../../../../bin/feenox -c case.fee 2>&1"), $output, $result);
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
}

// see if there's something to commit
exec("git status --porcelain", $output, $result);
if (count($output) > 0) {
  suncae_git_commit_all("problem {$field} = {$value}", $output, $result);
  if ($result != 0) {
    return_error_json("cannot git commit {$id}: {$output[0]} {$output[1]}");
  }
}
suncae_log("problem {$id} ajax2problem {$field} = {$value}");
if ($response["error"] != "") {
  suncae_log_error("case {$id} ajax2problem failed: {$response["error"]}");
}

return_back_json($response);
