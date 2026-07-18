#!/usr/bin/python3
import sys
import os

script_dir = os.path.dirname(os.path.abspath(__file__))
bin_dir = os.path.join(script_dir, '../../', 'bin')  # if bin is one level up
if os.path.exists(bin_dir):
  sys.path.insert(0, bin_dir)
import gmsh

import math
import json

# ------------------------------------------

if (len(sys.argv) < 3):
  print("need mesh file and Gmsh error level in the command line")
  sys.exit(1)

mesh_hash = sys.argv[1]
error = int(sys.argv[2])
error_file = "%s.2" % mesh_hash
error_message = ""
if os.path.exists(error_file):
  with open(error_file) as f:
    error_message = f.read()
  f.close()

meta = {}
meta["status"] = "success" if error == 0 else "error"
# meta["error"] = error_message


mesh_file = "%s.msh" % mesh_hash
if os.path.exists(mesh_file):
  gmsh.initialize()
  gmsh.option.setNumber("General.Terminal", 0)
  gmsh.open(mesh_file)

  nodes, _, _ = gmsh.model.mesh.getNodes()
  # print(nodes)
  meta["nodes"] = len(nodes);

# 1    2-node line. 
# 2    3-node triangle. 
# 3    4-node quadrangle. 
# 4    4-node tetrahedron. 
# 5    8-node hexahedron. 
# 6    6-node prism. 
# 7    5-node pyramid. 
# 8    3-node second order line (2 nodes associated with the vertices and 1 with the edge). 
# 9    6-node second order triangle (3 nodes associated with the vertices and 3 with the edges). 
# 10   9-node second order quadrangle (4 nodes associated with the vertices, 4 with the edges and 1 with the face). 
# 11   10-node second order tetrahedron (4 nodes associated with the vertices and 6 with the edges). 
# 12   27-node second order hexahedron (8 nodes associated with the vertices, 12 with the edges, 6 with the faces and 1 with the volume). 
# 13   18-node second order prism (6 nodes associated with the vertices, 9 with the edges and 3 with the quadrangular faces). 
# 14   14-node second order pyramid (5 nodes associated with the vertices, 8 with the edges and 1 with the quadrangular face). 
# 15   1-node point. 
# 16   8-node second order quadrangle (4 nodes associated with the vertices and 4 with the edges). 
# 17   20-node second order hexahedron (8 nodes associated with the vertices and 12 with the edges). 
# 18   15-node second order prism (6 nodes associated with the vertices and 9 with the edges). 
# 19   13-node second order pyramid (5 nodes associated with the vertices and 8 with the edges). 
# 20   9-node third order incomplete triangle (3 nodes associated with the vertices, 6 with the edges) 
# 21   10-node third order triangle (3 nodes associated with the vertices, 6 with the edges, 1 with the face) 
# 22   12-node fourth order incomplete triangle (3 nodes associated with the vertices, 9 with the edges) 
# 23   15-node fourth order triangle (3 nodes associated with the vertices, 9 with the edges, 3 with the face) 
# 24   15-node fifth order incomplete triangle (3 nodes associated with the vertices, 12 with the edges) 
# 25   21-node fifth order complete triangle (3 nodes associated with the vertices, 12 with the edges, 6 with the face) 
# 26   4-node third order edge (2 nodes associated with the vertices, 2 internal to the edge) 
# 27   5-node fourth order edge (2 nodes associated with the vertices, 3 internal to the edge) 
# 28   6-node fifth order edge (2 nodes associated with the vertices, 4 internal to the edge) 
# 29   20-node third order tetrahedron (4 nodes associated with the vertices, 12 with the edges, 4 with the faces) 
# 30   35-node fourth order tetrahedron (4 nodes associated with the vertices, 18 with the edges, 12 with the faces, 1 in the volume) 
# 31   56-node fifth order tetrahedron (4 nodes associated with the vertices, 24 with the edges, 24 with the faces, 4 in the volume) 
# 92   64-node third order hexahedron (8 nodes associated with the vertices, 24 with the edges, 24 with the faces, 8 in the volume) 
# 93   125-node fourth order hexahedron (8 nodes associated with the vertices, 36 with the edges, 54 with the faces, 27 in the volume) 


  elements = gmsh.model.mesh.getElements();
  # print(elements)
  for i in range(len(elements[0])):
    t = "elements"
    if elements[0][i] == 4 or elements[0][i] == 11:
      t = "tetrahedra"
    elif elements[0][i] == 15:
      t = "points"

    meta[t] = len(elements[2][i])

  # meta["elements"] = len(elements);

  if error == 0:

    # quality
    quality_type = "gamma"
    N = 10
    _, eleTags , _ = gmsh.model.mesh.getElements(dim=3)

    # qualities
    q = gmsh.model.mesh.getElementQualities(eleTags[0], quality_type)
    q_n = len(q)

    q_hist = [0]*(N+1)
    q_mean = 0
    q_var = 0
    q_min = 1
    q_max = 0
    for i in range(q_n):
      if q[i] < q_min:
        q_min = q[i]
      if q[i] > q_max:
        q_max = q[i]

      delta = q[i] - q_mean
      q_mean += delta / (i+1)
      delta2 = q[i] - q_mean
      q_var += delta * delta2

      q_hist[int(math.floor(q[i]*N))] += 1

    meta["q_mean"] = q_mean
    meta["q_dev"] = math.sqrt(q_var/q_n)
    meta["q_min"] = q_min
    meta["q_max"] = q_max

    q_dat = open("%s-quality.dat" % mesh_hash, "w") 

    # qualities = 1  go to N so we move them to N-1
    q_hist[N-1] += q_hist[N]
    q_y_max = -1.0
    for j in range(N):
      q_x = j/N + 1/(2*N)
      q_y = q_hist[j]/q_n
      if q_y > q_y_max:
        q_y_max = q_y
      q_dat.write("%g\t%g\n" % (q_x, q_y))

    q_dat.close() 

    # size
    emin = gmsh.model.mesh.getElementQualities(eleTags[0], "minEdge")
    emax = gmsh.model.mesh.getElementQualities(eleTags[0], "maxEdge")
    e_n = len(emin)
    e = [0]*(e_n)
  
    e_hist = [0]*(N+1)
    e_mean = 0
    e_var = 0
    e_min = 1e6
    e_max = 0
  
    for i in range(e_n):
      e[i] = 0.5*(emin[i] + emax[i])
      if e[i] < e_min:
        e_min = e[i]
      if e[i] > e_max:
        e_max = e[i]
      
    for i in range(e_n):
      delta = e[i] - e_mean
      e_mean += delta / (i+1)
      delta2 = e[i] - e_mean
      e_var += delta * delta2
  
      e_hist[int(math.floor(((e[i]-e_min)/(e_max-e_min))*N))] += 1
  
    meta["e_mean"] = e_mean
    meta["e_dev"] = math.sqrt(e_var/e_n)
    meta["e_min"] = e_min
    meta["e_max"] = e_max
  
    e_dat = open("%s-size.dat" % mesh_hash, "w") 
  
    # qualities = 1  go to N so we move them to N-1
    e_hist[N-1] += e_hist[N]
    e_y_max = -1.0
    for j in range(N):
      e_x = (j/N + 1/(2*N))*(e_max-e_min) + e_min
      e_y = e_hist[j]/e_n
      if e_y > e_y_max:
        e_y_max = e_y
      e_dat.write("%g\t%g\n" % (e_x, e_y))

    e_dat.close() 


    gp = open("%s.gp" % mesh_hash, "w") 
    # gp.write("set terminal svg size 160,120\n")
    gp.write("set terminal svg size 240,240\n")
    gp.write("set margins 4,2,4,1\n")
    gp.write("set tics scale 0.4\n")

    # quality
    # gp.write("set style fill solid 0.8 border -1 lc 1\n")
    gp.write("set yrange [0:%.3f]\n" % (1.25*q_y_max))
    gp.write("set style fill solid 0.8 border -1\n")
    # gp.write("set title \"mean quality = %.1f +/- %.1f\"\n" % (q_mean, math.sqrt(q_var/q_n)))
    gp.write("set xlabel \"element quality\"\n")
    # gp.write("set ylabel \"fraction\"\n")
    gp.write("set xtics 0.2\n")
    gp.write("set ytics 0.1\n")
    gp.write("set output \"%s-quality.svg\"\n" % mesh_hash)
    gp.write("plot \"%s-quality.dat\" with boxes  fillcolor \"dark-green\" fs solid border linecolor \"black\"    ti \"\"\n" % mesh_hash)

    # sizes
    # gp.write("set style fill solid 0.8 border -1 lc 2\n")
    gp.write("set yrange [0:%.3f]\n" % (1.25*e_y_max))
    # gp.write("set title \"mean size = %.1f +/- %.1f\"\n" % (e_mean, math.sqrt(e_var/q_n)))
    gp.write("set xlabel \"element size [mm]\"\n")
    # gp.write("set ylabel \"fraction\"\n")
    gp.write("set xtics auto\n")
    gp.write("set ytics 0.1\n")
    gp.write("set output \"%s-size.svg\"\n" % mesh_hash)
    gp.write("plot \"%s-size.dat\" with boxes  fillcolor \"dark-cyan\" fs solid border linecolor \"black\"   ti \"\"\n" % mesh_hash)

    gp.close()

  gmsh.finalize()

print(json.dumps(meta))
