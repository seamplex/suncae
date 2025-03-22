#!/usr/bin/python3
import sys
sys.path.append("../../../../bin")
import gmsh
import os
import gmsh
import math
import random
import colorsys
import json

def main():
  gmsh.initialize()
  gmsh.option.setNumber("General.Terminal", 0)
  
  gmsh.open("original.step")
  
# TODO: take into account surface colors
# groups = defaultdict(list)
# 
# for tag in gmsh.model.getEntities():
#   col = gmsh.model.getColor(*tag)
#   if col != (0, 0, 255, 0):
#     if(tag[0]==2):
#       print('entity', tag, 'color', col)
#       if col == (170, 0, 0, 255):
#         groups['Combustion_1'].append(tag[1])
#       elif col == (0, 0, 255, 255):
#         groups['Extrapolation_1'].append(tag[1])
#       else:
#         groups['Mur_1'].append(tag[1])
# 
# for name, tags in groups.items():
#   gmsh.model.addPhysicalGroup(2, tags, name=name)
# 
# for tag in gmsh.model.getEntities():
#   if(tag[0]==2):
#     print('entity',tag,'physical name:',gmsh.model.getPhysicalName(tag[0],tag[1]))


  

  solids = len(gmsh.model.getEntities(3))
  faces = len(gmsh.model.getEntities(2))
  edges = len(gmsh.model.getEntities(1))
  vertices = len(gmsh.model.getEntities(0))

  for i in range(vertices):
    gmsh.model.addPhysicalGroup(0, [1+i], 1+i)
    gmsh.model.setPhysicalName(0, 1+i, "vertex%d" % (1+i))
  for i in range(edges):
    gmsh.model.addPhysicalGroup(1, [1+i], 1+i)
    gmsh.model.setPhysicalName(1, 1+i, "edge%d" % (1+i))
  for i in range(faces):
    gmsh.model.addPhysicalGroup(2, [1+i], 1+i)
    gmsh.model.setPhysicalName(2, 1+i, "face%d" % (1+i))
  for i in range(solids):
    gmsh.model.addPhysicalGroup(3, [1+i], 1+i)
    gmsh.model.setPhysicalName(3, 1+i, "solid%d" % (1+i))

  gmsh.write("cad.xao")
  gmsh.finalize()

  # xao to x3d + json
  gmsh.initialize()
  gmsh.option.setNumber("General.Terminal", 0)
  gmsh.open("cad.xao")

  geometry = {}

  geometry["solids"] = solids
  geometry["faces"] = faces
  geometry["edges"] = edges
  geometry["vertices"] = vertices

  # bounding box global
  [xmin, ymin, zmin, xmax, ymax, zmax] = gmsh.model.getBoundingBox(-1, -1)

  geometry["xmin"] = xmin
  geometry["xmax"] = xmax
  geometry["ymin"] = ymin
  geometry["ymax"] = ymax
  geometry["zmin"] = zmin
  geometry["zmax"] = zmax

  # max_length
  max_length = math.sqrt(math.pow(xmax-xmin,2) + math.pow(ymax-ymin,2) + math.pow(zmax-zmin,2))
  max_delta = max(xmax-xmin, ymax-ymin, zmax-zmin)

  geometry["max_delta"] = max_delta
  geometry["max_length"] = max_length

  # grabamos el x3d (necesitamos el bounding box)
  gmsh.option.setNumber("Print.X3dSurfaces", 2)
  gmsh.option.setNumber("Print.X3dEdges", 2)
  gmsh.option.setNumber("Print.X3dVertices", 1)

  # TODO: elegir por commandline
  gmsh.option.setNumber("Mesh.StlLinearDeflection", 3e-3*max_delta);
  gmsh.option.setNumber("Mesh.StlAngularDeflection", 1);

  gmsh.write("cad.x3d")

  # boundaries y cogs

  # entities can be 0-dim, 1-dim, 2-dim or 3-dim
  entities = [[],[],[],[]]

  area_total = 0
  volume_total = 0     # contadores para el cog global
  num_x = 0
  num_y = 0
  num_z = 0

  for e in gmsh.model.getEntities():
    boundaries = gmsh.model.getBoundary([e], False, False, False)
    dim = e[0]
    tag = e[1]

    entity = {}
    entity["tag"] = tag
    entity["type"] = gmsh.model.getType(dim, tag)
    entity["name"] = gmsh.model.getEntityName(dim, tag)

    mass = gmsh.model.occ.getMass(dim, tag)
    entity["mass"] = mass

    if dim == 0:
      cog = gmsh.model.getValue(dim, tag, [])
    else:
      cog = gmsh.model.occ.getCenterOfMass(dim, tag);
      
    # the list() thing is to handle tuples returned by getCenterOfMass() with numpy      
    entity["cog"] = list(cog)

    if dim == 3:
      num_x += mass * cog[0]
      num_y += mass * cog[1]
      num_z += mass * cog[2]
      volume_total += mass
    elif dim == 2:
      area_total += mass

    entity["boundary"] = boundaries
    entities[dim].append(entity)


  if volume_total != 0:
    geometry["cog"] = [num_x/volume_total, num_y/volume_total, num_z/volume_total]
  else:
    geometry["cog"] = [0.5*(xmin+xmax), 0.5*(ymin+ymax), 0.5*(zmin+zmax)]

  geometry["volume"] = volume_total
  geometry["area"] = area_total

  # viewpoint
  factor = 0.8
  f = 8 * factor * max_delta

  geometry["position"] = ("%f %f %f" % (geometry["cog"][0]+0.55*f, geometry["cog"][1]-0.55*f, geometry["cog"][2]+0.75*f));
  # require_once $abs_us_root.$us_url_root."common/quaternion.php";
  # $json["orientation"] = quaternion2vector_angle(-0.353553, -0.146447, -0.353553, -0.853553);
  geometry["orientation"] = "-0.678597 -0.281085 -0.678597 5.18713"
  geometry["centerOfRotation"] = ("%f %f %f" % (geometry["cog"][0], geometry["cog"][1], geometry["cog"][2]));
  geometry["fieldOfView"] = ("[%f,%f,%f,%f]" % (-(1+2*factor)/2*max_delta, -(1+2*factor)/2*max_delta, (1+2*factor)/2*max_delta, (1+2*factor)/2*max_delta));

  # leemos el original
  data = open("original.json")
  geometry["orig"] = json.load(data)
  data.close()

  # colores (usamos el file size como semilla)
  colors = []
  # el primero (numero 0) es el gris
  colors.append([0.65, 0.65, 0.65])
  if solids > 1:
    phi = 2.0/(1.0 + math.sqrt(5.0))
    random.seed(geometry["orig"]["size"])
    h = random.random()
    for i in range(0, solids):
      colors.append(colorsys.hsv_to_rgb(h, 0.70, 0.50))
      h += 1.0/phi
      h %= 1

  geometry["color"] = colors
  gmsh.finalize()

  # save the jsons
  with open("cad.json", "w") as fp:
    json.dump(geometry, fp, indent=2)

  with open("entities.json", "w") as fp:
    json.dump(entities, fp, indent=2)


if __name__ == "__main__":
  main()

