<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

include("../uxs/faster-than-quick/labels.php");

$k = "1";
$q3 = "0";
$bc = array();
$material_by_label = array();

function heat_parse_material_line($line) {
  $trimmed = trim($line);
  if (preg_match('/^MATERIAL\s+([A-Za-z0-9_.-]+)\s*(.*)$/', $trimmed, $matches) !== 1) {
    return null;
  }
  $label = $matches[1];
  $tokens = array_values(array_filter(str_getcsv(isset($matches[2]) ? $matches[2] : "", ' ', '"'), function($token) {
    return $token !== "";
  }));
  $properties = array();
  foreach ($tokens as $token) {
    if (preg_match('/^(k|q)=(.+)$/', $token, $property_match) === 1) {
      $properties[$property_match[1]] = trim($property_match[2]);
    }
  }
  return array("label" => $label, "properties" => $properties);
}

function heat_parse_material_function_line($line) {
  if (preg_match('/^(k|q)_([A-Za-z0-9_.-]+)\s*\(x,y,z\)\s*=\s*(.+)$/', trim($line), $matches) === 1) {
    return array(
      "property" => $matches[1],
      "label" => $matches[2],
      "expression" => trim($matches[3])
    );
  }
  return null;
}

$fee = fopen("../data/{$owner}/cases/{$id}/case.fee", "r");
if ($fee) {
  $bc_i = 0;
  // TODO: allow spacing, spaces in regexps?
  while (($line = fgets($fee)) !== false) {
    if (strncmp("k(x,y,z) =", $line, 10) == 0 || strncmp("k(x,y,z)=", $line, 9) == 0) {
      preg_match('/k\(x,y,z\)[ ]*=[ ]*\((.*)\)\*1e-3/', $line, $matches);
      if (count($matches) == 2) {
        $k = $matches[1];
      } else {
        $k = sprintf("(%s)*1e3", substr($line, strpos($line, "=")+1));
      }
    } else if (strncmp("k =", $line, 3) == 0 || strncmp("k=", $line, 2) == 0) {
      preg_match('/k[ ]*=[ ]*\((.*)\)\*1e-3/', $line, $matches);
      if (count($matches) == 2) {
        $k = $matches[1];
      } else {
        $k = sprintf("(%s)*1e3", substr($line, strpos($line, "=")+1));
      }

    } else if (strncmp("q(x,y,z) =", $line, 10) == 0 || strncmp("q(x,y,z)=", $line, 9) == 0) {
      preg_match('/q\(x,y,z\)[ ]*=[ ]*(.*)/', $line, $matches);
      if (count($matches) == 2) {
        $q3 = $matches[1];
      } else {
        $q3 = substr($line, strpos($line, "=")+1);
      }
    } else if (strncmp("q =", $line, 3) == 0 || strncmp("q=", $line, 2) == 0) {
      preg_match('/q[ ]*=[ ]*(.*)/', $line, $matches);
      if (count($matches) == 2) {
        $q3 = $matches[1];
      } else {
        $q3 = substr($line, strpos($line, "=")+1);
      }

    } else if (($material_function = heat_parse_material_function_line($line)) != null) {
      if (isset($material_by_label[$material_function["label"]]) == false) {
        $material_by_label[$material_function["label"]] = array();
      }
      $material_by_label[$material_function["label"]][$material_function["property"]] = $material_function["expression"];

    } else if (($material_line = heat_parse_material_line($line)) != null) {
      if (isset($material_by_label[$material_line["label"]]) == false) {
        $material_by_label[$material_line["label"]] = array();
      }
      foreach ($material_line["properties"] as $property_name => $property_expression) {
        $material_by_label[$material_line["label"]][$property_name] = $property_expression;
      }

      
    } else if (strncmp("BC ", $line, 3) == 0) {

      // TODO: make a function      
      // let's parse the existing BC
      $line_exploded = explode(" ", $line);
      $bc_name = $line_exploded[1];
      $i = 2;
      $n_values = 0;
      $n_groups = 0;
      $entity = array();
      $bc_group = array();
      while (isset($line_exploded[$i])) {
        if ($line_exploded[$i] == "GROUPS") {
          while (isset($line_exploded[++$i])) {
            preg_match('/(?P<name>\w+?)(?P<digit>\d+)/', $line_exploded[$i], $matches);
            $entity[$n_groups] = $matches[1];
            $bc_group[$n_groups++] = $matches[2];
          }
          break;
        } else if ($line_exploded[$i] == "GROUP") {
          $i++;
          preg_match('/(?P<name>\w+)(?P<digit>\d+)/', $line_exploded[$i], $matches);
          $entity[$n_groups] = $matches[1];
          $bc_group[$n_groups++] = $matches[2];
        } else {
          $bc_value[$n_values++] = $line_exploded[$i];
        }
        $i++;
      }

      if ($n_groups == 0) {
        preg_match('/(?P<name>\w+)(?P<digit>\d+)/', $bc_name, $matches);
        if ($matches[1] == "face") {
          $entity[0] = $matches[1];
          $bc_group[0] = $matches[2];
          $n_groups = 1;
        }  
      }
      
      // TODO: check they are all the same
      if ($n_groups > 0) {
        $bc[$bc_i]["entities"] = $entity[0];
        $bc[$bc_i]["groups"] = "";
        $first = true;
        foreach ($bc_group as $group) {
          if ($first == false) {
            $bc[$bc_i]["groups"] .= ",";
          } else {
            $first = false;
          }
          $bc[$bc_i]["groups"] .= $group;
        }
      
        $bc[$bc_i]["value"] = "";
        $first = true;
        foreach ($bc_value as $value) {
          if ($first == false) {
            $bc[$bc_i]["value"] .= " ";
          } else {
            $first = false;
          }
          $bc[$bc_i]["value"] .= $value;
        }
        $bc_i++;
      }
    }
  }
} else {
  echo "error opening fee";
  exit();
}

$material_labels = array();
$cad_json_path = "../data/{$owner}/cads/{$case["cad"]}/cad.json";
if (file_exists($cad_json_path)) {
  $cad_json = json_decode(file_get_contents($cad_json_path), true);
  if ($cad_json != null && isset($cad_json["solids"])) {
    for ($solid = 1; $solid <= intval($cad_json["solids"]); $solid++) {
      $material_labels[] = "solid{$solid}";
    }
  }
}
foreach (array_keys($material_by_label) as $label_name) {
  if (in_array($label_name, $material_labels, true) == false) {
    $material_labels[] = $label_name;
  }
}
sort($material_labels, SORT_NATURAL);

// TODO: use the ones from the javascript: create a script to create both php and js
$color = array();

$color[0]  = [0.00, 0.00, 0.00];
$color[1]  = [0.60, 0.30, 0.90];
$color[2]  = [1.00, 0.33, 0.33];
$color[3]  = [0.67, 0.77, 0.37];
$color[4]  = [0.16, 0.83, 1.00];
$color[5]  = [0.40, 1.00, 0.40];
$color[6]  = [1.00, 0.90, 0.50];
$color[7]  = [0.40, 0.80, 0.85];
$color[8]  = [1.00, 0.00, 0.40];
$color[9]  = [0.16, 0.83, 0.00];
$color[10] = [1.00, 0.40, 0.00];

title_left("Problem definition");
push_accordion("problem");
push_accordion_item("bcs", "problem", "Conditions &amp; loads", true);
?>   
    <div class="row m-1 p-1">
     <button class="btn btn-outline-primary w-100" onclick="bc_add('custom')">
      <i class="bi bi-plus-circle me-2"></i>Add boundary condition
     </button>
    </div> 
   
    <div class="accordion" id="accordion_bcs">   

<?php
for ($i = 0; $i < 10; $i++) {

  $bc_type = "custom";
  $custom_value = ($i < count($bc)) ? $bc[$i]["value"] : $default_bc[$problem];

  $T = "0";
  if ($i < count($bc) && str_contains($bc[$i]["value"], "T=")) {
    preg_match('/T=([^\s]*)/', $bc[$i]["value"], $matches);
    $T = $matches[1];
    $bc_type = "temperature";
  }

  $q2 = "0";
  if ($i < count($bc) && str_contains($bc[$i]["value"], "q=")) {
    preg_match('/q=([^\s]*)/', $bc[$i]["value"], $matches);
    $q2 = $matches[1];
    $bc_type = "heatflux";
  }

  $h = "0";
  if ($i < count($bc) && str_contains($bc[$i]["value"], "h=")) {
    preg_match('/h=([^\s]*)/', $bc[$i]["value"], $matches);
    $h = $matches[1];
    $bc_type = "convection";
  }

  $Tref = "0";
  if ($i < count($bc) && str_contains($bc[$i]["value"], "Tref=")) {
    preg_match('/Tref=([^\s]*)/', $bc[$i]["value"], $matches);
    $Tref = $matches[1];
    $bc_type = "convection";
  }

?>
 <div class="accordion-item <?=($i < count($bc)) ? "d-block" : "d-none" ?>" id="div_bc_<?=$i+1?>">
  <h2 class="accordion-header" id="heading_bc_<?=$i+1?>">
   <button id="button_bc_<?=$i+1?>" class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_bc_<?=$i+1?>" aria-expanded="false" aria-controls="collapse_bc_<?=$i+1?>" style="background-color: rgb(<?=255*$color[1+$i][0]?>, <?=255*$color[1+$i][1]?>, <?=255*$color[1+$i][2]?>)">
    Boundary condition <?=$i+1?>
   </button>
  </h2>
  <div id="collapse_bc_<?=$i+1?>" class="accordion-collapse collapse" aria-labelledby="heading_bc_<?=$i+1?>" data-bs-parent="#accordion_bcs">
   <div class="accordion-body pt-3 px-1 pb-2">

    <div class="row mb-1">
     <div class="col-4">
      <select class="form-select" id="bc_what_<?=$i+1?>" onchange="bc_change_filter(<?=$i+1?>, this.value)">
       <option value="2"<?=($i < count($bc) && $bc[$i]["entities"] == "face") ? " selected" : ""?>>Faces</a>
       <option value="1"<?=($i < count($bc) && $bc[$i]["entities"] == "edge") ? " selected" : ""?>>Edges</a>
       <option value="0"<?=($i < count($bc) && $bc[$i]["entities"] == "point") ? " selected" : ""?>>Vertices</a>
      </select>
     </div> 

     <div class="col-8">
      <div class="input-group">
       <input type="text" class="form-control" name="bc_<?=$i+1?>_groups" id="text_bc_<?=$i+1?>_groups" value="<?=($i < count($bc)) ? $bc[$i]["groups"] : ""?>" onblur="bc_update_from_text(<?=$i+1?>)" disabled>
       <button class="btn btn-light dropdown-toggle" type="button" id="button_dropdown_bc_<?=$i+1?>" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-three-dots"></i>
       </button>
       <ul class="dropdown-menu" aria-labelledby="button_dropdown_bc_<?=$i+1?>">
        <li>
         <a class="dropdown-item" href="#" onclick="document.getElementById('text_bc_<?=$i+1?>_groups').disabled = false">
          <i class="bi bi-123 me-2"></i>Edit numerical selection
         </a>
        </li>
        <li>
         <a class="dropdown-item" href="#" onclick="fee_show()">
          <i class="bi bi-pencil-square me-2"></i>Edit full solver input
         </a>
        </li>
<!-- TODO: disable
        <li><a class="dropdown-item disabled" aria-disabled="true">Disable condition</a></li>
-->
        <li><hr class="dropdown-divider"></li>
        <li>
         <a class="dropdown-item text-danger" href="#" onclick="bc_remove(<?=$i+1?>)">
          <i class="bi bi-trash me-2"></i>Remove condition
         </a>
        </li>
<!--        
        <li><hr class="dropdown-divider"></li>
        <li>
         <a class="dropdown-item" href="#">
          <i class="bi bi-question-circle me-2"></i>Help
         </a>
        </li>
-->
       </ul>
      </div> 
     </div>
     
    </div>

    <div class="row mb-1">
     <div class="col-4">
      <select class="form-select" id="bc_what_<?=$i+1?>" onchange="bc_update_type(<?=$i+1?>, this.value)">
       <option value="custom"       <?=($bc_type == "custom")?"selected":""?>>Custom</a>
       <option value="temperature"  <?=($bc_type == "temperature")?"selected":""?>>Temperature</a>
       <option value="heatflux"     <?=($bc_type == "heatflux")?"selected":""?>>Heat Flux</a>
       <option value="convection"   <?=($bc_type == "convection")?"selected":""?>>Convection</a>
      </select>
     </div> 
     
     <!-- custom  -->
     <div class="col-8 <?=($bc_type == "custom")?"":"d-none"?>" id="bc_value_<?=$i+1?>_custom">
      <input type="text" class="form-control" name="bc_<?=$i+1?>_value" id="text_bc_<?=$i+1?>_value" value="<?=$custom_value?>" onblur="ajax2problem(this.name, this.value)">
     </div>
     
     <!-- temperature -->
     <div class="col-8 <?=($bc_type == "temperature")?"":"d-none"?>" id="bc_value_<?=$i+1?>_temperature">
      <div class="input-group">
       <span class="input-group-text"><?=$label["T="]?></span>
       <input type="text" class="form-control" name="bc_<?=$i+1?>_value" id="text_bc_<?=$i+1?>_T" value="<?=$T?>" onblur="ajax2problem(this.name, 'T='+this.value)">
       <span class="input-group-text"><?=$label["K"]?></span>
      </div>
     </div>

     <!-- heatflux -->
     <div class="col-8 <?=($bc_type == "heatflux")?"":"d-none"?>" id="bc_value_<?=$i+1?>_heatflux">
      <div class="input-group">
       <span class="input-group-text"><?=$label["q2="]?></span>
       <input type="text" class="form-control" name="bc_<?=$i+1?>_value" id="text_bc_<?=$i+1?>_q" value="<?=$q2?>" onblur="ajax2problem(this.name, 'q='+this.value)">
       <span class="input-group-text"><?=$label["Wmm-2K"]?></span>
      </div>
     </div>

     <!-- convection -->
     <div class="col-8 <?=($bc_type == "convection")?"":"d-none"?>" id="bc_value_<?=$i+1?>_convection">
      <div class="input-group">
       <span class="input-group-text"><?=$label["h="]?></span>
       <input type="text" class="form-control" name="bc_<?=$i+1?>_value" id="text_bc_<?=$i+1?>_h" value="<?=$h?>" onblur="ajax2problem(this.name, 'h='+this.value+' Tref='+text_bc_<?=$i+1?>_Tref.value)">
       <span class="input-group-text"><?=$label["Wmm-2K-1"]?></span>
      </div>

      <div class="input-group">
       <span class="input-group-text"><?=$label["Tref="]?></span>
       <input type="text" class="form-control" name="bc_<?=$i+1?>_value" id="text_bc_<?=$i+1?>_Tref" value="<?=$Tref?>" onblur="ajax2problem(this.name, 'h='+text_bc_<?=$i+1?>_h.value+' Tref='+this.value)">
       <span class="input-group-text"><?=$label["K"]?></span>
      </div>

     </div>
    </div>
    

   </div>
  </div>
 </div>
<?php
}
?>   
    </div>
    
<?php
pop_accordion_item();
push_accordion_item("materialproperties", "problem", "Material properties", false);
?>
    <div class="row mt-2 mb-1">
     <label for="material_model" class="col-6 col-form-label text-end">Thermal conduction model</label>
     <div class="col-6">
      <select class="form-select" id="material_model" onchange="">
       <option value="linear_elastic_isotropic">Isotropic</a>
      </select>
     </div> 
    </div> 

    <div class="row mt-2 mb-1">
     <label for="text_name" class="col-4 col-form-label text-end"><?=$label["k="]?></label>
     <div class="col-8">
      <div class="input-group">
       <input type="text" class="form-control" name="k" id="text_k" value="<?=$k?>" onblur="ajax2problem(this.name, this.value)">
       <!-- TODO: choose units -->
       <span class="input-group-text"><?=$label["Wm-1K-1"]?></span>
      </div>
     </div>
    </div> 
    <div class="row mt-2 mb-1">
     <label for="text_q" class="col-4 col-form-label text-end"><?=$label["q3="]?></label>
     <div class="col-8">
      <div class="input-group">
       <input type="text" class="form-control" name="q" id="text_q" value="<?=$q3?>" onblur="ajax2problem(this.name, this.value)">
       <span class="input-group-text"><?=$label["Wmm-3"]?></span>
       <button class="btn btn-light dropdown-toggle" type="button" id="button_dropdown_bc_<?=$i+1?>" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-three-dots"></i>
       </button>
       <ul class="dropdown-menu" aria-labelledby="button_dropdown_bc_<?=$i+1?>">
        <li>
         <a class="dropdown-item" href="#" onclick="fee_show()">
          <i class="bi bi-pencil-square me-2"></i>Edit full solver input
         </a>
        </li>
<!--        
        <li><hr class="dropdown-divider"></li>
        <li>
         <a class="dropdown-item" href="#">
          <i class="bi bi-question-circle me-2"></i>Help
         </a>
        </li>
-->
       </ul>
      </div>
     </div>
    </div>

<?php if (count($material_labels) > 0) { ?>
    <div class="row mt-3 mb-1">
     <div class="col-12 small text-muted text-center">Per-label properties from <code>case.fee</code> (`MATERIAL` or `k_label`/`q_label`)</div>
    </div>
<?php
  foreach ($material_labels as $material_label_name) {
    $material_k = isset($material_by_label[$material_label_name]["k"]) ? $material_by_label[$material_label_name]["k"] : "";
    $material_q = isset($material_by_label[$material_label_name]["q"]) ? $material_by_label[$material_label_name]["q"] : "";
?>
    <div class="row mt-2 mb-1">
     <label class="col-2 col-form-label text-end"><?=$material_label_name?></label>
     <div class="col-4">
      <div class="input-group">
       <span class="input-group-text">k=</span>
       <input type="text" class="form-control" name="mat_<?=$material_label_name?>_k" value="<?=htmlspecialchars($material_k)?>" placeholder="1.0" onblur="ajax2problem(this.name, this.value)">
      </div>
     </div>
     <div class="col-4">
      <div class="input-group">
       <span class="input-group-text">q=</span>
       <input type="text" class="form-control" name="mat_<?=$material_label_name?>_q" value="<?=htmlspecialchars($material_q)?>" placeholder="0" onblur="ajax2problem(this.name, this.value)">
      </div>
     </div>
     <div class="col-2 small text-muted pt-2">label</div>
    </div>
<?php
  }
?>
<?php } ?>
   
 
<?php
pop_accordion_item();
push_accordion_item("input", "problem", "Solver input", false); 
?>
 
    <div class="row m-1 p-1">
     <div class="btn-group" role="group">
      <button class="btn btn-outline-primary w-100" onclick="fee_show()">
       <i class="bi bi-pencil-square me-2"></i>Show &amp; edit solver input
      </button>
<!--      
      <button class="btn btn-outline-info">
       <i class="bi bi-question-circle"></i>
      </button>
-->
     </div>    
    </div>
 
<?php
pop_accordion_item();
pop_accordion();
?>


<!--  buttons -->

<div class="d-grid mx-2 mt-4">
 <div class="btn-group w-100" role="group">
  <button class="btn w-25 btn-info" type="button" id="button_back" onclick="change_step(1)">
   <i class="bi bi-arrow-left-short mx-1"></i>
   Mesh
  </button>

<?php
if ($has_results) {
?>
  <button class="btn w-75 btn-secondary" type="button" id="button_next" onclick="change_step(3)">
   <i class="bi bi-arrow-right mx-2"></i>
   View results
  </button>
<?php
} else if ($has_mesh_valid) {
?>
  <button class="btn w-75 btn-secondary" type="button" id="button_next" onclick="change_step(3)">
   <i class="bi bi-arrow-right mx-2"></i>
   Solve problem
  </button>
<?php
} else  {
?>
  <button class="btn w-75 btn-secondary disabled" disabled type="button" id="button_next">
   <i class="bi bi-arrow-right mx-2"></i>
   Solve problem
  </button>
<?php
}
?>

 </div>
</div>

<?php
for ($i = 0; $i < 10; $i++) {
?>

<!-- https://stackoverflow.com/questions/4057236/how-to-add-onload-event-to-a-div-element -->
<!--  <img src onerror="document.getElementById('button_bc_<?=$i+1?>').style.backgroundColor('red')"> -->
 <img src onerror="document.getElementById('collapse_bc_<?=$i+1?>').addEventListener('shown.bs.collapse', () => { current_bc = <?=$i+1?>; current_dim = document.getElementById('bc_what_<?=$i+1?>').value;})">
 <img src onerror="document.getElementById('collapse_bc_<?=$i+1?>').addEventListener('hide.bs.collapse', () => { current_bc = 0; current_dim = 2;})">

<?php
}
?>

<img src onerror="n_bcs = <?=count($bc)?>; cad_update_colors();">
