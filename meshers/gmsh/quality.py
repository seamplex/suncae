#!/usr/bin/python3
import sys
import os

script_dir = os.path.dirname(os.path.abspath(__file__))
bin_dir = os.path.join(script_dir, '../../', 'bin')  # if bin is one level up
if os.path.exists(bin_dir):
  sys.path.insert(0, bin_dir)
import gmsh

import math

gmsh.initialize(sys.argv)
gmsh.option.setNumber("General.Terminal", 0)

gmsh.open("mesh.msh")

## get element qualities:
#_, eleTags , _ = gmsh.model.mesh.getElements(dim=3)
#q = gmsh.model.mesh.getElementQualities(eleTags[0], "minSICN")
#print(zip(eleTags[0], q))

gmsh.plugin.setNumber("AnalyseMeshQuality", "JacobianDeterminant", 1.)
gmsh.plugin.setNumber("AnalyseMeshQuality", "IGEMeasure", 1.)
gmsh.plugin.setNumber("AnalyseMeshQuality", "ICNMeasure", 1.)

gmsh.plugin.setNumber("AnalyseMeshQuality", "CreateView", 1.)
gmsh.plugin.run("AnalyseMeshQuality")

dataType, tags, Jac, time, numComp = gmsh.view.getModelData(0, 0)
dataType, tags, IGE, time, numComp = gmsh.view.getModelData(1, 0)
dataType, tags, ICN, time, numComp = gmsh.view.getModelData(2, 0)

N = 20
hist_Jac = [0]*(N+1)
hist_IGE = [0]*(N+1)
hist_ICN = [0]*(N+1)


for i in range(len(tags)):
  n_Jac = int(math.floor(Jac[i][0]*N))
  n_IGE = int(math.floor(IGE[i][0]*N))
  n_ICN = int(math.floor(ICN[i][0]*N))
  #print(n_Jac, n_IGE, n_ICN)
  hist_Jac[n_Jac] += 1
  hist_IGE[n_IGE] += 1
  hist_ICN[n_ICN] += 1

# for qualities = 1 they go to N and we move it to N-1
hist_Jac[N-1] += hist_Jac[N]
hist_IGE[N-1] += hist_IGE[N]
hist_ICN[N-1] += hist_ICN[N]


for j in range(N):
  print("{0}\t{1}\t{2}\t{3}".format(j,hist_Jac[j],hist_IGE[j],hist_ICN[j]))

gmsh.finalize()
