<?php

// TODO: improve
function solver_input_write_initial($filename, $problem) {
  $fee = fopen($filename, "w");
  fprintf($fee, "PROBLEM mechanical MESH meshes/%s-2.msh\n", md5_file("mesh.geo"));
  fprintf($fee, "\n");
  fprintf($fee, "E(x,y,z) = (200)*1e3\n");
  fprintf($fee, "nu = 0.3\n");
  fprintf($fee, "\n");
  fprintf($fee, "SOLVE_PROBLEM\n");
  fprintf($fee, "WRITE_RESULTS FORMAT vtk all\n");

  fclose($fee);
}
