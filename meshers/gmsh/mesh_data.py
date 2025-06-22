#!/usr/bin/python3
import sys
sys.path.append("../../../../bin")
import gmsh
import os
import json

# Add a segment, always in increasing order, to make duplicate detection easy
def addLine2(lines, lines_set, tags, first, second):
  a = int(tags[first])
  b = int(tags[second])
  entry = (min(a-1, b-1), max(a-1, b-1))
  if entry not in lines_set:
    lines.append([entry[0], entry[1]])
    lines_set.add(entry)
  return

def addLine3(lines, lines_set, tags, first, second, third):
  a = int(tags[first])
  b = int(tags[second])
  c = int(tags[third])
  # Always store in the lowest, middle, largest order (for consistency)
  sorted_entry = tuple(sorted([a-1, b-1, c-1]))
  if sorted_entry not in lines_set:
    lines.append(list(sorted_entry))
    lines_set.add(sorted_entry)
  return

# ------------------------------------------

if (len(sys.argv) < 3):
  print("need mesh hash and dir in the command line")
  sys.exit(1)

mesh_file = "%s/%s.msh" % (sys.argv[2], sys.argv[1])
if not os.path.exists(mesh_file):
  print("mesh file does not exist")
  sys.exit(1)

print("1", flush=True)
gmsh.initialize()
gmsh.option.setNumber("General.Terminal", 0)
gmsh.open(mesh_file)

# TODO: read whether the mesh is curved or not
curved = 0

mesh = {}

# nodes ------------
mesh["nodes"] = ""
tags, coord, _ = gmsh.model.mesh.getNodes()
maxtag = max(tags)
for i in range(maxtag):
  mesh["nodes"] += "{:6g} {:6g} {:6g}  ".format(coord[3*i+0], coord[3*i+1], coord[3*i+2])

# surface edges ------------
mesh["surfaces_edges_set"] = ""
elements = gmsh.model.mesh.getElements(2)
n_elements = len(elements)
lines = []
lines_set = set()
i = 0
k = 0
for type in elements[0]:
  j = 0
  if type == 2 or (curved == 0 and type == 9):    # 3-node triangle
    for element in elements[1][i]:
      addLine2(lines, lines_set, elements[2][i], j+0, j+1)
      addLine2(lines, lines_set, elements[2][i], j+1, j+2)
      addLine2(lines, lines_set, elements[2][i], j+2, j+0)
      j += 3 if type == 2 else 6 
      k += 1
  elif type == 9:  # 6-node triangle
    for element in elements[1][i]:
      addLine3(lines, lines_set, elements[2][i], j+0, j+3, j+1)
      addLine3(lines, lines_set, elements[2][i], j+1, j+4, j+2)
      addLine3(lines, lines_set, elements[2][i], j+2, j+5, j+0)
      j += 6
      k += 1
  i += 1

print("2", flush=True)

n_lines = len(lines)
k = 0
for line in lines:
  if (len(line) == 2):
    mesh["surfaces_edges_set"] += "{:d} {:d} -1 ".format(line[0], line[1])
  elif (len(line) == 3):
    mesh["surfaces_edges_set"] += "{:d} {:d} {:d} -1 ".format(line[0], line[1], line[2])
  k += 1  
print("3", flush=True)

# surface faces, one per each physical group ------------
mesh["surfaces_faces_set"] = {}
physicals = gmsh.model.getPhysicalGroups()
k = 0
for physical in physicals:
  dim = physical[0]
  physical_tag = physical[1]
  if (dim == 2):
    mesh["surfaces_faces_set"][physical_tag] = ""
    for entity in gmsh.model.getEntitiesForPhysicalGroup(dim, physical_tag):
      types, tags, nodetags = gmsh.model.mesh.getElements(dim, entity)
      for i in range(len(types)):
        for j in range(len(tags[i])):
          if types[i] == 2 or (curved == 0 and types[i] == 9):
            N = 6 if (types[i] == 9) else 3
            # for triangles remove the last int and the -1
            mesh["surfaces_faces_set"][physical_tag] += "{:d} {:d} {:d} ".format(int(nodetags[i][j*N+0])-1, int(nodetags[i][j*N+1])-1, int(nodetags[i][j*N+2])-1)
          elif types[i] == 9:
            N = 6
            mesh["surfaces_faces_set"][physical_tag] += "{:d} {:d} {:d} ".format(int(nodetags[i][j*N+0])-1, int(nodetags[i][j*N+3])-1, int(nodetags[i][j*N+5])-1)
            mesh["surfaces_faces_set"][physical_tag] += "{:d} {:d} {:d} ".format(int(nodetags[i][j*N+1])-1, int(nodetags[i][j*N+4])-1, int(nodetags[i][j*N+3])-1)
            mesh["surfaces_faces_set"][physical_tag] += "{:d} {:d} {:d} ".format(int(nodetags[i][j*N+2])-1, int(nodetags[i][j*N+5])-1, int(nodetags[i][j*N+4])-1)
            mesh["surfaces_faces_set"][physical_tag] += "{:d} {:d} {:d} ".format(int(nodetags[i][j*N+3])-1, int(nodetags[i][j*N+4])-1, int(nodetags[i][j*N+5])-1)
          k += 1  

print("4", flush=True)
gmsh.finalize()

with open("%s/%s-data.json" % (sys.argv[2], sys.argv[1]), "w", encoding ='utf8') as json_file:
  json.dump(mesh, json_file)
