#!/usr/bin/python3
import sys
import os

script_dir = os.path.dirname(os.path.abspath(__file__))
bin_dir = os.path.join(script_dir, '../../', 'bin')  # if bin is one level up
if os.path.exists(bin_dir):
  sys.path.insert(0, bin_dir)
import gmsh

import math
import random
import colorsys
import json

def main():
  gmsh.initialize()
  gmsh.option.setNumber("General.Terminal", 0)
  
  gmsh.open("original.step")

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

  # bounding box global
  [xmin, ymin, zmin, xmax, ymax, zmax] = gmsh.model.getBoundingBox(-1, -1)

  # max_length
  max_length = math.sqrt(math.pow(xmax-xmin,2) + math.pow(ymax-ymin,2) + math.pow(zmax-zmin,2))
  max_delta = max(xmax-xmin, ymax-ymin, zmax-zmin)

  # grabamos el stl
  gmsh.option.setNumber("Geometry.OCCBoundsUseStl", 1);
  gmsh.option.setNumber("Mesh.StlOneSolidPerSurface", 2)

  # TODO: elegir por commandline
  gmsh.option.setNumber("Mesh.StlLinearDeflection", 3e-3*max_delta);
  gmsh.option.setNumber("Mesh.StlLinearDeflectionRelative", 1e-2); 
  gmsh.option.setNumber("Mesh.StlAngularDeflection", 1);

  gmsh.write("cad.stl")
  gmsh.write("cad.ply")
  gmsh.finalize()

if __name__ == "__main__":
  main()

