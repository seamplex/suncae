#!/usr/bin/python3
import sys
sys.path.append("../../bin")
sys.path.append("../../../../bin")

import gmsh

# Initialize the Gmsh API
gmsh.initialize()

# Get the Gmsh version string
version = gmsh.option.getString("General.Version")

# Print the version
print(version)

# Finalize the Gmsh API
gmsh.finalize()
