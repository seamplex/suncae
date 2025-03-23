<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

$mesh_geo = array();
$geo = fopen("{$case_dir}/mesh.geo", "r");
if ($geo) {
  $i = 0;
  while (($line = fgets($geo)) !== false) {
    if (strncmp("Mesh.", $line, 5) == 0) {
      preg_match('/Mesh.(.*) = (.*);/', $line, $matches);
      if (count($matches) == 3) {
        $mesh_geo[$i]["variable"] = $matches[1];
        $mesh_geo[$i]["value"] = $matches[2];
        $i++;
      }
    }
  }
} else {
  echo "error opening {$case_dir}/mesh.geo";
  exit();
}


title_left("Customize mesh");
push_accordion("mesh");
// TODO: if coming from another step, show this one otherwise show the other one first
push_accordion_item("currentmesh", "mesh", "Current mesh <span class=\"badge badge-light\">".substr($mesh_hash,0,7)."</span>", true); 
row_set_width(5);

if ($has_mesh_valid) {
  if (isset($mesh_meta["nodes"])) {
    row_ro("First-order nodes", number_format($mesh_meta["nodes"]), $mesh_meta["nodes"] < 25e3);
  }
  if (isset($mesh_meta["tetrahedra"])) {
    row_ro("Tetrahedra", number_format($mesh_meta["tetrahedra"]));
  }
  if (isset($mesh_meta["e_mean"])) {
    row_ro("Element size", sprintf("<span class=\"math inline\">(%.2g ± %.2g)&nbsp;mm ∈ [%.2g : %.2g]</span>", $mesh_meta["e_mean"], $mesh_meta["e_dev"],  $mesh_meta["e_min"],  $mesh_meta["e_max"]));
  }

  if (isset($mesh_meta["q_mean"])) {
    row_ro("Mesh quality", sprintf("<span class=\"math inline\">%.2g ± %.2g</span>", $mesh_meta["q_mean"], $mesh_meta["q_dev"]));
  
    // TODO: check the svgs exist
?>
    <div class="row p-0 m-0">
     <div class="col-6 p-0 m-0">
      <img class="img-fluid p-0 m-0" src="mesh_graph.php?id=<?=$id?>&file=<?=$mesh_hash?>-size">
     </div>
     <div class="col-6 p-0 m-0">
      <img class="img-fluid p-0 m-0" src="mesh_graph.php?id=<?=$id?>&file=<?=$mesh_hash?>-quality">
     </div>
    </div>
<?php
  }
} else if ($has_mesh_attempt) {
  
  if ($mesh_meta["status"] == "canceled") {
?>
    <div class="small alert alert-warning">
     The meshing process was canceled.
    </div> 
    
    <button class="btn btn-lg btn-outline-success w-100" onclick="relaunch_meshing('<?=$mesh_hash?>')">
     <i class="bi bi-arrow-repeat me-2"></i>&nbsp;Re-launch meshing
    </button>
<?php
  } else if ($mesh_meta["status"] == "syntax_error") {
?>
<pre class="small alert alert-danger">
<?=file_get_contents("{$cad_dir}/meshes/{$mesh_hash}-check.2")?>
</pre>

<?php
  } else {
?>
<pre class="small alert alert-danger">
<?php

    $error_file = fopen("{$cad_dir}/meshes/{$mesh_hash}.2", "r");
    while (($line = fgets($error_file, 4096)) !== false) {
      $line_exploded = explode(":", $line);
      for ($j = 1; $j < count($line_exploded); $j++) {
        printf("%s", $line_exploded[$j]);
      }
    }
    fclose($error_file);
?>
</pre>
<?php
  }
} else  { 
?>  
    <div id="error_message" class="small alert alert-dismissible alert-warning">
     There is no mesh nor any attempt at it.
    </div> 
<?php
}

pop_accordion_item();
push_accordion_item("updatemesh", "mesh", "Customize &amp; update mesh"); 

// TODO: structures/classes
$desc["MeshSizeMax"] = "Target element size [mm]";
$type["MeshSizeMax"] = "float";  // TODO: enums

$cad = json_decode(file_get_contents("../data/{$username}/cads/{$case["cad"]}/cad.json"), true);
$length_order = pow(10, floor(log10($cad["max_length"])));
$length_precision = 1e-2 * $length_order;
$length_char = 4 * $cad["volume"]/$cad["area"];

$lc = 0;
$lc_min = 0; 
foreach($mesh_geo as $mesh_var) {
  if ($mesh_var["variable"] == "MeshSizeMax") {
    $lc = $mesh_var["value"];
  } else if ($mesh_var["variable"] == "MeshSizeMin") {
    $lc_min = $mesh_var["value"];
  }
}

if ($lc == 0) {
  if (isset($mesh_meta["e_mean"])) {
    // TODO: make a round_to_p() function
    $lc = ceil($mesh_meta["e_mean"] / $length_precision) * $length_precision;
  } else {
    $lc = ceil($length_char / $length_precision) * $length_precision;
  }
}

$lc_rounded = ceil($lc / $length_precision) * $length_precision;


$default["MeshSizeMax"] = $lc;
$min["MeshSizeMax"] = 0.1*$lc_rounded;
$max["MeshSizeMax"] = 2.0*$lc_rounded;
$step["MeshSizeMax"] = 0.1*$length_precision;

// --------------------------

$desc["MeshSizeMin"] = "Minimum element size [mm]";
$type["MeshSizeMin"] = "float";  // TODO: enums

if ($lc_min == 0) {
  $lc_min = 1e-2*$lc;
}

$default["MeshSizeMin"] = $lc_min;
$min["MeshSizeMin"] = 0.1*$lc_min;
$max["MeshSizeMin"] = 2.0*$lc_min;
$step["MeshSizeMin"] = 0.001*$length_precision;

// -------------------

$desc["MeshSizeFromCurvature"] = "Nodes per circle";
$type["MeshSizeFromCurvature"] = "int";
$default["MeshSizeFromCurvature"] = 8;
$min["MeshSizeFromCurvature"] = 4;
$max["MeshSizeFromCurvature"] = 64;

$desc["MeshSizeExtendFromBoundary"] = "Extend size from boundary";
$type["MeshSizeExtendFromBoundary"] = "int";
$default["MeshSizeExtendFromBoundary"] = 1;
$min["MeshSizeExtendFromBoundary"] = 0;
$max["MeshSizeExtendFromBoundary"] = 1;


$desc["Optimize"] = "Basic optimization";
// $type["Optimize"] = "bool";
// $default["Optimize"] = true;
$type["Optimize"] = "int";
$default["Optimize"] = 1;
$min["Optimize"] = 0;
$max["Optimize"] = 1;

$desc["OptimizeNetgen"] = "Netgen optimization";
// $type["OptimizeNetgen"] = "bool";
// $default["OptimizeNetgen"] = true;
$type["OptimizeNetgen"] = "int";
$default["OptimizeNetgen"] = 1;
$min["OptimizeNetgen"] = 0;
$max["OptimizeNetgen"] = 1;


// chatgpt told me that the word was "areal"
// TODO: int with combo
$desc["Algorithm"] = "Areal algorithm";
$type["Algorithm"] = "int";
$default["Algorithm"] = 6;
$min["Algorithm"] = 1;
$max["Algorithm"] = 10;

$desc["Algorithm3D"] = "Volumetric algorithm";
$type["Algorithm3D"] = "int";
$default["Algorithm3D"] = 1;
$min["Algorithm3D"] = 1;
$max["Algorithm3D"] = 6;


foreach($desc as $variable => $description) {
  $used[$variable] = false;
}


$n_existing = count($mesh_geo);
$n_extra = 5;
$n_total = $n_existing + $n_extra;

for ($i = $n_existing; $i < $n_total; $i++) {
  $mesh_geo[$i]["variable"] = "";
  $mesh_geo[$i]["value"] = "";
}
  

if ($n_existing == 0) {
?>

<p class="text-center" id="p_default_options">
Using default meshing options.
</p>

   
<?php
}  
  
for ($i = 0; $i < $n_total; $i++) {
  $known_field = false;
  foreach($desc as $variable => $description) {
    if ($mesh_geo[$i]["variable"] == $variable){
      $known_field = true;
      $used[$variable] = true;
    }
  }
  
  $variable = $mesh_geo[$i]["variable"];

  if ($known_field) {
    // TODO: enums
    if ($type[$variable] == "float") {
      row_form_range($variable, $desc[$variable], $mesh_geo[$i]["value"], "flex", $min[$variable], $max[$variable], $step[$variable], "mesh_field_update", "ajax2mesh", "mesh_field_remove");
    } else if ($type[$variable] == "int") {
      row_form_int($variable,
                   $desc[$variable],
                   $mesh_geo[$i]["value"],
                   "flex",
                   $min[$variable],
                   $max[$variable],
                   1,
                   "ajax2mesh", "mesh_field_remove");
    } else {
?>
    <div class="row mb-1 <?=($i < $n_existing)?"d-flex":"d-none"?>" id="row_mesh_<?=$i+1?>">
     <label for="mesh_<?=$i+1?>_value" class="col-6 col-form-label text-end"><?=$desc[$mesh_geo[$i]["variable"]]?></label>
     <div class="col-4">
      <input type="text" class="form-control" name="mesh_<?=$i+1?>_value" id="text_mesh_<?=$i+1?>_value" value="<?=$mesh_geo[$i]["value"]?>" onblur="ajax2mesh(this.name, this.value)">
     </div>
     
     <div class="col-2">
      <div class="dropdown">
       <button class="btn btn-outline-danger dropdown-toggle" type="button" id="button_dropdown_remove_mesh_<?=$i+1?>" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-trash"></i>
       </button>
       <ul class="dropdown-menu" aria-labelledby="button_dropdown_remove_mesh_<?=$i+1?>">
        <li><a class="dropdown-item" href="#" onclick="mesh_field_remove(<?=$i+1?>)">Remove setting</a></li>
       </ul>
      </div>     
     </div> 
    </div>
<?php
    }
  } else {
?>
    <div class="row mb-1 <?=($i < $n_existing)?"":"d-none"?>" id="row_mesh_<?=$i+1?>">
     <div class="col-4">
      <input type="text" class="form-control" name="mesh_<?=$i+1?>_value" id="text_mesh_<?=$i+1?>_value" value="<?=$mesh_geo[$i]["value"]?>" onblur="ajax2mesh(this.name, this.value)">
     </div>
     <div class="col-2">
      <div class="dropdown">
       <button class="btn btn-outline-danger dropdown-toggle" type="button" id="button_dropdown_remove_mesh_<?=$i+1?>" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-trash"></i>
       </button>
       <ul class="dropdown-menu" aria-labelledby="button_dropdown_remove_mesh_<?=$i+1?>">
        <li><a class="dropdown-item" href="#" onclick="mesh_field_remove(<?=$i+1?>)">Remove setting</a></li>
       </ul>
      </div>     
     </div> 
    </div>
<?php
  }
}

foreach($desc as $variable => $description) {
  if ($used[$variable] == false) {
    if ($type[$variable] == "float") {
      row_form_range($variable,
                     $desc[$variable],
                     $default[$variable],
                     "none",
                     $min[$variable],
                     $max[$variable],
                     $step[$variable],
                     "mesh_field_update",
                     "ajax2mesh",
                     "mesh_field_remove");
    } else if ($type[$variable] == "int") {
      row_form_int($variable,
                   $desc[$variable],
                   $default[$variable],
                   "none",
                   $min[$variable],
                   $max[$variable],
                   1,
                   "ajax2mesh",
                   "mesh_field_remove");
    }
  }
}

?>

<!-- TODO: call ajax2mesh with the default value (how???) -->
    <div class="row mt-3 mb-1">
     <div class="input-group w-100" role="group">
      <select id="select_add_mesh_field" name="add_mesh_field" class="form-select" onchange="mesh_field_add(this.value)">
       <option value="add" selected>Add new setting</option>
<?php
foreach($desc as $variable => $description) {
  if ($used[$variable] == false) {
?>
       <option value="<?=$variable?>"><?=$description?></option>
<?php
  }
}
?>
<!--        <option value="custom">Custom setting</option> -->
      </select>
      <button class="btn w-50 btn-success" type="button" id="button_next" onclick="change_step(1)">
       <i class="bi bi-arrow-repeat mx-2"></i>
       Update mesh
      </button>
     </div>
    </div>

<?php
pop_accordion_item();
push_accordion_item("expertmesh", "mesh", "Mesher input &amp; output", false);
?>

    <div class="row m-1 p-1">
     <div class="btn-group" role="group">
      <button class="btn btn-outline-secondary w-100" onclick="geo_show()">
       <i class="bi bi-pencil-square me-2"></i>Show &amp; edit mesher input
      </button>
      <button class="btn btn-outline-info">
       <i class="bi bi-question-circle"></i>
      </button>
     </div>
    </div>
<?php
if (file_exists("{$case_dir}/run/meshes/{$mesh_hash}.msh")) {
?>
    <div class="row m-1 p-1">
     <div class="btn-group" role="group">
      <a class="btn btn-success w-100" href="mesh_msh.php?id=<?=$id?>&hash=<?=$mesh_hash?>">
       <i class="bi bi-download me-2"></i>Download MSH
      </a>
      <button class="btn btn-success">
       <i class="bi bi-question-circle"></i>
      </button>
     </div>
    </div>
<?php
}

pop_accordion_item();
push_accordion_item("meshhistory", "mesh", "Meshing history", false);
?>

To be done

<?php
pop_accordion_item();
pop_accordion();
?>

<div class="d-grid mx-2 mt-4">
 <div class="btn-group w-100" role="group">
  <button class="btn w-100 btn-secondary" type="button" id="button_back" onclick="change_step(2)">
   <i class="bi bi-arrow-right-short mx-1"></i>
   Problem
  </button>
 </div>
</div>

<!-- <img src onerror="n_mesh_field=<?=$n_existing?>"> -->
