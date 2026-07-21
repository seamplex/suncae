#!/usr/bin/python3
import os
import sys

script_dir = os.path.dirname(os.path.abspath(__file__))
bin_dir = os.path.join(script_dir, '../../', 'bin')
if os.path.exists(bin_dir):
  sys.path.insert(0, bin_dir)
import gmsh


def _volume_entities():
  return list(gmsh.model.getEntities(3))


def _fuse_all(volumes):
  merged = [volumes[0]]
  for volume in volumes[1:]:
    merged, _ = gmsh.model.occ.fuse(merged, [volume], removeObject=True, removeTool=True)
    merged = [entity for entity in merged if entity[0] == 3]
  gmsh.model.occ.synchronize()


def _fragment_all(volumes):
  gmsh.model.occ.fragment([volumes[0]], volumes[1:], removeObject=True, removeTool=True)
  gmsh.model.occ.synchronize()


def main():
  if len(sys.argv) != 4:
    print("usage: cadtreat.py <mode> <input.step> <output.step>")
    sys.exit(2)

  mode = sys.argv[1]
  input_step = sys.argv[2]
  output_step = sys.argv[3]

  if mode not in ["single_material", "multi_material"]:
    print("invalid mode")
    sys.exit(2)

  gmsh.initialize()
  gmsh.option.setNumber("General.Terminal", 0)

  try:
    gmsh.open(input_step)
  except Exception:
    gmsh.finalize()
    print("cannot open input STEP")
    sys.exit(1)

  volumes = _volume_entities()
  if len(volumes) == 0:
    gmsh.finalize()
    print("no solids found")
    sys.exit(3)

  if len(volumes) > 1:
    if mode == "single_material":
      _fuse_all(volumes)
    elif mode == "multi_material":
      _fragment_all(volumes)

  if len(_volume_entities()) == 0:
    gmsh.finalize()
    print("operation produced zero solids")
    sys.exit(4)

  output_dir = os.path.dirname(output_step)
  if output_dir != "":
    os.makedirs(output_dir, exist_ok=True)

  gmsh.write(output_step)
  gmsh.finalize()
  sys.exit(0)


if __name__ == "__main__":
  main()
