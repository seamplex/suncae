<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

include("ux.php");
include("labels.php");

if (isset($case["name"]) == false) {
  $case["name"] = "Default name";
}
if (isset($case["owner"]) == false) {
  $case["onwer"] = "anonymous";
}

// TODO: keep it in the fee?
// or keep a copy in the yaml so as to check the user did not change it?
// chdir("../data/{$username}/cases/{$id}");
// $problem["type"] = shell_exec("grep ^PROBLEM case.fee | awk '{print $2}'");

title_left("Properties");
push_accordion("properties");
push_accordion_item("case", "properties", "Case properties", true);
row_set_width(3);
row_form("name", "Name", $case["name"]);
row_form_code("id", "Id", substr($case["id"],0,7));
row_form_ro("text_owner", "Owner", $case["owner"]);
row_form_ro("text_type", "Type", ucfirst($problem));
?>

   <div class="row mb-1">
    <label for="text_visibility" class="col-3 col-form-label text-end">Visibility</label>
    <div class="col-9">
     <select class="form-select" name="visibility" id="text_visibility" onchange="ajax2yaml(this.name, this.value)">
      <option value="public"  <?=($case["visibility"]=="public")?"selected":""?>>Public</option>
      <option value="private" <?=($case["visibility"]=="private")?"selected":""?>>Private</option>
     </select>
    </div> 
   </div> 

<?php
row_form_ro("text_created", "Created", date(DATE_RFC2822, $case["date"]));
row_form_ro("text_solver", "Mesher", $case["mesher"]);
row_form_ro("text_solver", "Solver", $case["solver"]);
pop_accordion_item();

 // chdir("../data/{$username}/cases/{$id}");
if (($cad_json = file_get_contents("../data/{$username}/cads/{$case["cad"]}/cad.json")) == false) {
  return_error("cannot find cad {$case["cad"]}");
}

if (($cad = json_decode($cad_json, true)) == null) {
  return_error("cannot decode cad {$id}");
}


push_accordion_item("cad", "properties", "CAD properties", false);
row_set_width(5);
row_ro("Solids", $cad["solids"]);
row_ro("Faces", $cad["faces"]);
row_ro("Edges", $cad["edges"]);
row_ro("Vertices", $cad["vertices"]);

row_ro_units("Bounding box ".$label["xmin"], number_format($cad["xmin"], 2), $label["mm"]);
row_ro_units("Bounding box ".$label["xmax"], number_format($cad["xmax"], 2), $label["mm"]);
row_ro_units("Bounding box ".$label["ymin"], number_format($cad["ymin"], 2), $label["mm"]);
row_ro_units("Bounding box ".$label["ymax"], number_format($cad["ymax"], 2), $label["mm"]);
row_ro_units("Bounding box ".$label["zmin"], number_format($cad["zmin"], 2), $label["mm"]);
row_ro_units("Bounding box ".$label["zmax"], number_format($cad["xmax"], 2), $label["mm"]);
row_ro_units("Diagonal", number_format($cad["max_length"], 2), $label["mm"]);
row_ro_units("Center of Gravity", number_format($cad["cog"][0], 2), $label["mm"]);
row_ro_units("", number_format($cad["cog"][1], 2), $label["mm"]);
row_ro_units("", number_format($cad["cog"][1], 2), $label["mm"]);
row_ro_units("Area", number_format($cad["area"], 0), $label["mm2"]);
row_ro_units("Volume", number_format($cad["volume"], 0), $label["mm3"]);

pop_accordion_item();
pop_accordion();
?>

