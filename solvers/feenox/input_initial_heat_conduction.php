<?php

function solver_input_write_initial($filename) {
  $solids = 1;
  if (file_exists("case.yaml")) {
    $case = @yaml_parse_file("case.yaml");
    if (is_array($case) && isset($case["cad"]) && $case["cad"] != "") {
      $cad_json_path = sprintf("../../cads/%s/cad.json", $case["cad"]);
      if (file_exists($cad_json_path)) {
        $cad_json = json_decode(file_get_contents($cad_json_path), true);
        if (is_array($cad_json) && isset($cad_json["solids"])) {
          $solids_from_cad = intval($cad_json["solids"]);
          if ($solids_from_cad > 1) {
            $solids = $solids_from_cad;
          }
        }
      }
    }
  }

  $fee = fopen($filename, "w");
  fprintf($fee, "PROBLEM thermal MESH meshes/%s.msh\n", md5_file("mesh.geo"));
  fprintf($fee, "\n");
  if ($solids > 1) {
    for ($solid = 1; $solid <= $solids; $solid++) {
      fprintf($fee, "MATERIAL solid%d k=(1)*1e-3 q=0\n", $solid);
    }
  } else {
    fprintf($fee, "k(x,y,z) = (1)*1e-3\n");
    fprintf($fee, "q(x,y,z) = 0\n");
  }
  fprintf($fee, "\n");
  fprintf($fee, "SOLVE_PROBLEM\n");
  fprintf($fee, "WRITE_RESULTS FORMAT vtk all\n");
    
  fclose($fee);
}
