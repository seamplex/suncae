#!/usr/bin/python3
import os
import sys
import json


def mesh(attempt):
  os.system("timeout 30 ../../../../meshers/gmsh/trymesh.py %d > attempt%d.log 2>&1" % (attempt, attempt))
  if os.path.exists("attempt%d.json" % attempt):
    fp = open("attempt%d.json" % attempt)
    result = json.load(fp)
    fp.close()
    return result["result"] == "success" 
  else:
    return False

def main():

  # write a basic default.geo and close it so it's available in case it's needed
  geo = open("default.geo", "w")
  geo.write("Merge \"../../cads/%s/cad.xao\";\n" % os.path.basename(os.path.normpath(os.getcwd())))
  geo.close()

  # try to obtain a valid mesh
  i = 0;
  i_max = 5;
  while mesh(i) == False:
    print("%d-th attempt failed" % (i))
    i += 1
    if i == i_max+1:
      print("max attempts reached")
      sys.exit(1)

  print("final success with attempt = %d" % i)    

  # append what worked
  geo = open("default.geo", "a")
  if True:
    print(i)
    fp = open("attempt%d.json" % i)
    attempt = json.load(fp)
    fp.close()

    print(attempt)
    if attempt["lc"] != 0:
      lc = float(attempt["lc"])
      geo.write("Mesh.MeshSizeMax = %g;\n" % (lc));
      geo.write("Mesh.MeshSizeMin = %g;\n" % (1e-2*lc));

    default = open("../../../../meshers/gmsh/default%d.geo" % i)
    geo.write(default.read())
    default.close()
  geo.close()

  if os.path.exists("meshes") == False:
    os.system("../../../../meshers/gmsh/mesh.sh cad");
  sys.exit(0)

if __name__ == "__main__":
  main()
