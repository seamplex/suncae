# New case

 #. if someone goes to the root `index.php` (at `html/index.php`) without and `id` or directly to `new/` then the "new case" page at `new/index.php` is shown
 #. that one includes `uxs/$ux/new.php`

## Faster-than-quick UX

 #. `uxs/faster-than-quick/new.php` shows the four choices

     2. Physics
     3. Problem
     4. Solver
     5. Mesher

   But the first one, which is the CAD, comes from `uxs/faster-than-quick/importers/$cadimporter.php`

## Upload-importer

 #. `uxs/faster-than-quick/importers/upload.php` shows a file upload HTML component with an onchange call to `upload_cad_file()` that after uploading the CAD, calls `process_cad()`.
 #. `process_cad()` in `uxs/faster-than-quick/new.php` calls `html/new/process.php` which includes `cadprocessors/$cadprocessor/process.php`

## Gmsh-processor

 #. `cadprocessors/gmsh/process.php` uses `cadimport.py` to process the uploaded CAD with Gmsh (through OCC) to create a `.xao`
 #. It calls `initial_mesh.sh` in background to create the first mesh.
 #. This `initial_mesh.sh` just calls `cadmesh.py` if `default.geo` does not exit.
 #. `cadmesh.py` performs five attempts to create an initial mesh by calling `trymesh.py` with the number of attempt $i$ as the argument
 #. `trymesh.py` merges `defaulti.geo` and then tries to come up with a max $\ell_c$
 #. 

