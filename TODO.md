# Roadmap

 * more problems (non-linear mechanics, transient thermal, modal, cfd, etc.)
 * more meshers (netgen? tetgen? salome?)
 * more solvers (sparselizard? ccx? fenics?)
 * more runners (ssh, aws, kubernetes, etc.)
 * more documentation

# TODOs

## General

 * unit tests
 * choose units (SI, etc.)
 * choose points for BCs (and eventually refinements)
 * name in the BC should reflect the content
 * dashboard with case list
 * real-time collaboration
 * detect changes in CAD
 * git history in the UX
 * show face id when hovering
 * screenshots
 * once a minute refresh the axes, faces, edges, etc. (take a snapshot?)
 * investigate defeature operation in OCC through Gmsh (would we need a separate UX?)
 * re-implement how to show SunCAE version in about (when running `deps.sh`)
 * check that everything is fine when running `deps.sh`:
   - that executables work
   - that permissions are fine
   - create a `txt` with versions with `git log -1` + `git status --porcelain`
 * ability to take notes in markdown
 * help ballons, markdown + pandoc
 * limit DOFs: in conf? somewhere in auth? like `limits.php`?
 * remove visibility, everything is public


## Gmsh

 * STL input
 * combos for algorithms
 * checkboxes for bool
 * local refinements
 * understand failures -> train AI to come up with a proper `.geo`
 * other meshers! (tetget? netgen?)
 * multi-solid: bonded, glued, contact
 * curved tetrahedra
 * hexahedra

## Problem

 * choose faces with ranges e.g 1-74
 * other problems: modal
 * other solvers: ccx, sparselizard
 * orthotropic elasticity
 * thermal expansion (isotropic and orthotropic)
 * modal feenox
 * mechanical sparselizard
 * transient/quasistatic (a slider for time?)

## Results

 * fields (the list of available fields should be read from the outpt vtk/msh)
   - heat flux? (more general, vector fields?)
 * the server should tell the client
   - which field it is returning (so the client can choose the pallete)
   - if it has a warped field or not
 * the range scale has to be below the warp slider
 * nan color (yellow)
 * compute the .dat in the PHP, not in Bash
 * probes: user picks location, server returns all field
 * reactions: choose which BCs to compute reaction at in the problem step with a checkboxes
 * warning for large deformations/stresses

## Outer loops

 * parametric
 * optimization
 
## Dashboard

 * list of cases

## Backlog

 * zoom over mouse
 * disable BCs (comment them out)
