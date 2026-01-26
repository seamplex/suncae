#!/usr/bin/python3
import sys
import os

script_dir = os.path.dirname(os.path.abspath(__file__))
bin_dir = os.path.join(script_dir, '../../', 'bin')  # if bin is one level up
if os.path.exists(bin_dir):
  sys.path.insert(0, bin_dir)

import gmsh

# Initialize the Gmsh API
gmsh.initialize()

# Get the Gmsh version string
version = gmsh.option.getString("General.Version")

# Print the version
print(version)

# Finalize the Gmsh API
gmsh.finalize()
