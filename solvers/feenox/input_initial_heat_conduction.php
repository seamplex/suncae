<?php

function solver_input_write_initial($filename) {
  $fee = fopen($filename, "w");
  fprintf($fee, "PROBLEM thermal MESH meshes/%s.msh\n", md5_file("mesh.geo"));
  fprintf($fee, "\n");
  fprintf($fee, "k(x,y,z) = (1)*1e-3\n");
  fprintf($fee, "q(x,y,z) = 0\n");
  fprintf($fee, "\n");
  fprintf($fee, "SOLVE_PROBLEM\n");
  fprintf($fee, "WRITE_RESULTS FORMAT vtk all\n");
    
  fclose($fee);
}
