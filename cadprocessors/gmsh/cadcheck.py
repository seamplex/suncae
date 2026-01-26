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

def main():
  gmsh.initialize()
  gmsh.option.setNumber("General.Terminal", 0)
  
  try:
    gmsh.open("original.step")
  except:
    print("invalid STEP");
    sys.exit(1)
  
  try:
    [xmin, ymin, zmin, xmax, ymax, zmax] = gmsh.model.getBoundingBox(-1, -1)
  
  except:
    print("invalid CAD");
    sys.exit(2)
  
  orig = {}
  orig["xmin"] = xmin
  orig["xmax"] = xmax
  orig["ymin"] = ymin
  orig["ymax"] = ymax
  orig["zmin"] = zmin
  orig["zmax"] = zmax
  orig["max_length"] = math.sqrt(math.pow(xmax-xmin,2) + math.pow(ymax-ymin,2) + math.pow(zmax-zmin,2))
  orig["max_delta"] = max(xmax-xmin, ymax-ymin, zmax-zmin)
  # orig["tolerance"] = 7e-5*orig["max_length"] if options.ext != "brep" else 0
  
  orig["solids"] = len(gmsh.model.getEntities(3))
  orig["faces"] = len(gmsh.model.getEntities(2))
  orig["edges"] = len(gmsh.model.getEntities(1))
  orig["vertices"] = len(gmsh.model.getEntities(0))
  
  # orig["format"] = options.ext
  orig["format"] = "step"
  orig["size"] = os.path.getsize("original.step")
  
  
  
  with open("original.json", "w") as fp:
    json.dump(orig, fp, indent=2)

  
  gmsh.finalize()
  sys.exit(0);


if __name__ == "__main__":
  main()
  
