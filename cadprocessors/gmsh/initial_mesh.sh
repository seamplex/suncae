#!/bin/bash

if [ ! -e default.geo ]; then
  python ../../../../meshers/gmsh/cadmesh.py
fi
