<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

include("../uxs/faster-than-quick/labels.php");

$pc = "default";
$E = "200";
$nu = "0.3";
$alpha = "0";
$bc = array();

$fee = fopen("../data/{$username}/cases/{$id}/case.fee", "r");
if ($fee) {
  $bc_i = 0;
  // TODO: allow spacing, spaces in regexps?
  while (($line = fgets($fee)) !== false) {
    $line_exploded = explode(" ", $line);

    $i = 0;
    if ($line_exploded[0] == "PROBLEM") {
      while (isset($line_exploded[$i])) {
        if ($line_exploded[$i] == "PC") {
          $pc = trim($line_exploded[++$i]);
        }
        $i++;
      }
    } else if (strncmp("E(x,y,z) = ", $line, 4) == 0) {
      preg_match('/E\(x,y,z\) = \((.*)\)\*1e3/', $line, $matches);
      if (count($matches) == 2) {
        $E = $matches[1];
      } else {
        $E = sprintf("(%s)*1e-3", substr($line, 4));
      }
    } else if (strncmp("nu = ", $line, 5) == 0) {
      $nu = substr($line, 5);

    } else if ($line_exploded[0] == "BC") {

      // TODO: make a function      
      // let's parse the existing BC
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

// TODO: use the ones from the javascript: create a script to create both php and js
$color = array();
$color[0] = [0, 0, 0];
$color[1] = [0.82, 0.22, 0.68];
$color[2] = [0.37, 0.85, 0.16];
$color[3] = [0.93, 0.91, 0.26];
$color[4] = [0.33, 0.86, 1.00];
$color[5] = [1.00, 0.66, 0.66];
$color[6] = [1.00, 0.50, 0.50];
$color[7] = [0.00, 0.80, 1.00];
$color[8] = [0.55, 0.37, 0.82];
$color[9] = [0.00, 1.00, 0.80];
$color[10] = [1.00, 0.40, 0.00];
$color[10] = [0.10, 0.25, 0.50];
$color[11] = [1.00, 0.80, 0.16];
$color[12] = [0.75, 0.15, 0.90];
$color[13] = [0.55, 0.01, 0.22];
$color[14] = [0.17, 0.38, 0.01];
$color[15] = [0.78, 0.44, 0.21];
$color[16] = [1.00, 0.25, 0.50];
$color[17] = [0.25, 0.83, 0.60];
$color[18] = [0.35, 0.75, 0.60];
$color[19] = [0.21, 0.78, 0.78];

title_left("Problem definition");
push_accordion("problem");
push_accordion_item("bcs", "problem", "Constraints &amp; Loads", true);
?>
    <div class="row m-1 p-1">
     <button class="btn btn-outline-primary w-100" onclick="bc_add('custom')">
      <i class="bi bi-plus-circle me-2"></i>Add boundary condition
     </button>
    </div> 
    <div class="accordion" id="accordion_bcs"> 
<!-- start div class="accordion" id="accordion_bcs" -->

<?php
for ($i = 0; $i < 10; $i++) {

  $bc_type = "custom";
  $custom_value = ($i < count($bc)) ? $bc[$i]["value"] : $default_bc[$problem];

  $u = ($i < count($bc)) && str_contains($bc[$i]["value"], "u=0");
  $v = ($i < count($bc)) && str_contains($bc[$i]["value"], "v=0");
  $w = ($i < count($bc)) && str_contains($bc[$i]["value"], "w=0");
  if ($u || $v || $w) {
    $bc_type = "fixture";
  }

  $p = "0";
  if ($i < count($bc) && str_contains($bc[$i]["value"], "p=")) {
    preg_match('/p=([^\s]*)/', $bc[$i]["value"], $matches);
    $p = $matches[1];
    $bc_type = "pressure";
  }
  
  $Fx = "0";
  $Fy = "0";
  $Fz = "0";
  if ($i < count($bc) && str_contains($bc[$i]["value"], "Fx=")) {
    preg_match('/Fx=([^\s]*)/', $bc[$i]["value"], $matches);
    $Fx = $matches[1];
    $bc_type = "force";
  }
  if ($i < count($bc) && str_contains($bc[$i]["value"], "Fy=")) {
    preg_match('/Fy=([^\s]*)/', $bc[$i]["value"], $matches);
    $Fy = $matches[1];
    $bc_type = "force";
  }
  if ($i < count($bc) && str_contains($bc[$i]["value"], "Fz=")) {
    preg_match('/Fz=([^\s]*)/', $bc[$i]["value"], $matches);
    $Fz = $matches[1];
    $bc_type = "force";
  }
  
  // TODO: what if there are mixtures i.e. u=0 p=1? keep it as "custom"?
  
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
       <option value="2"<?=($i < count($bc) && $bc[$i]["entities"] == "face") ? " selected" : ""?>>Faces</option>
       <option value="1"<?=($i < count($bc) && $bc[$i]["entities"] == "edge") ? " selected" : ""?>>Edges</option>
       <option value="0"<?=($i < count($bc) && $bc[$i]["entities"] == "point") ? " selected" : ""?>>Vertices</option>
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
<!-- TODO: tip: edit the input to enter complicated stuff -->
<!-- TODO: help? here or in "custom" -->
<!-- TODO: disable
        <li><a class="dropdown-item disabled" aria-disabled="true">Disable condition</a></li>
-->
        <li><hr class="dropdown-divider"></li>
        <li>
         <a class="dropdown-item text-danger" href="#" onclick="bc_remove(<?=$i+1?>)">
          <i class="bi bi-trash me-2"></i>Remove condition
         </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
         <a class="dropdown-item" href="#">
          <i class="bi bi-question-circle me-2"></i>Help
         </a>
        </li>
       </ul>
      </div> 
     </div>
    </div>

    <div class="row mb-1">
     <div class="col-4">
      <select class="form-select" id="bc_what_<?=$i+1?>" onchange="bc_update_type(<?=$i+1?>, this.value)">
       <option value="custom"   <?=($bc_type == "custom")?"selected":""?>>Custom</option>
       <option value="fixture"  <?=($bc_type == "fixture")?"selected":""?>>Fixture</option>
       <option value="pressure" <?=($bc_type == "pressure")?"selected":""?>>Pressure</option>
       <option value="force"    <?=($bc_type == "force")?"selected":""?>>Force</option>
      </select>
     </div> 

     <!-- custom  -->
     <div class="col-8 <?=($bc_type == "custom")?"":"d-none"?>" id="bc_value_<?=$i+1?>_custom">
      <input type="text" class="form-control" name="bc_<?=$i+1?>_value" id="text_bc_<?=$i+1?>_value" value="<?=$custom_value?>" onblur="ajax2problem(this.name, this.value)">
     </div>

     <!-- fixture -->
     <div class="col-8 <?=($bc_type == "fixture")?"":"d-none"?>" id="bc_value_<?=$i+1?>_fixture">
      <div class="row">

       <div class="col-4 pt-2">
        <div class="form-check form-switch">
         <input class="form-check-input" type="checkbox" role="switch" id="bc_<?=$i+1?>_fixture_u" <?=($u || $i >= count($bc))?"checked":""?> onblur="bc_fixture_update(<?=$i+1?>)">
         <label class="form-check-label" for="bc_<?=$i+1?>_fixture_u"><?=$label["u"]?></label>
        </div>
       </div> 

       <div class="col-4 pt-2">
        <div class="form-check form-switch">
         <input class="form-check-input" type="checkbox" role="switch" id="bc_<?=$i+1?>_fixture_v" <?=($v || $i >= count($bc))?"checked":""?> onblur="bc_fixture_update(<?=$i+1?>)">
         <label class="form-check-label" for="bc_<?=$i+1?>_fixture_v"><?=$label["v"]?></label>
        </div>
       </div> 

       <div class="col-4 pt-2">
        <div class="form-check form-switch">
         <input class="form-check-input" type="checkbox" role="switch" id="bc_<?=$i+1?>_fixture_w" <?=($w || $i >= count($bc))?"checked":""?> onblur="bc_fixture_update(<?=$i+1?>)">
         <label class="form-check-label" for="bc_<?=$i+1?>_fixture_w"><?=$label["w"]?></label>
        </div>
       </div> 

      </div>
     </div>

     <!-- pressure -->
     <div class="col-8 <?=($bc_type == "pressure")?"":"d-none"?>" id="bc_value_<?=$i+1?>_pressure">
      <div class="input-group">
       <span class="input-group-text"><?=$label["p="]?></span>
       <input type="text" class="form-control" name="bc_<?=$i+1?>_value" id="text_bc_<?=$i+1?>_p" value="<?=$p?>" onblur="ajax2problem(this.name, 'p='+this.value)">
       <span class="input-group-text"><?=$label["MPa"]?></span>
      </div>
     </div>
    
     <!-- force -->
     <div class="col-8 <?=($bc_type == "force")?"":"d-none"?>" id="bc_value_<?=$i+1?>_force">
      <div class="row">
       <div class="col input-group">
        <span class="input-group-text"><?=$label["Fx="]?></span>
        <input type="text" class="form-control" name="bc_<?=$i+1?>_value" id="text_bc_<?=$i+1?>_Fx" value="<?=$Fx?>" onblur="ajax2problem(this.name, 'Fx='+this.value + ' Fy='+text_bc_<?=$i+1?>_Fy.value + ' Fz='+text_bc_<?=$i+1?>_Fz.value)">
        <span class="input-group-text"><?=$label["N"]?></span>
       </div> 
      </div>
      <div class="row">
       <div class="col input-group">
        <span class="input-group-text"><?=$label["Fy="]?></span>
        <input type="text" class="form-control" name="bc_<?=$i+1?>_value" id="text_bc_<?=$i+1?>_Fy" value="<?=$Fy?>" onblur="ajax2problem(this.name, 'Fx='+text_bc_<?=$i+1?>_Fx.value + ' Fy='+this.value + ' Fz='+text_bc_<?=$i+1?>_Fz.value)">
        <span class="input-group-text"><?=$label["N"]?></span>
       </div> 
      </div>
      <div class="row">
       <div class="col input-group">
        <span class="input-group-text"><?=$label["Fz="]?></span>
        <input type="text" class="form-control" name="bc_<?=$i+1?>_value" id="text_bc_<?=$i+1?>_Fz" value="<?=$Fz?>" onblur="ajax2problem(this.name, 'Fx='+text_bc_<?=$i+1?>_Fx.value + ' Fy='+text_bc_<?=$i+1?>_Fy.value + ' Fz='+this.value)">
        <span class="input-group-text"><?=$label["N"]?></span>
       </div> 
      </div>
     </div>
    </div>
    

   </div>
  </div>
 </div>
<?php
}
?>
<!-- end div class="accordion" id="accordion_bcs" -->
    </div>

<?php
pop_accordion_item();
push_accordion_item("materialproperties", "problem", "Material properties", false);
?>

    <div class="row mt-2 mb-1">
     <label for="material_model" class="col-4 col-form-label text-end">Mechanical model</label>
     <div class="col-8">
      <select class="form-select" id="material_model" onchange="">
       <option value="linear_elastic_isotropic">Linear elastic isotropic</option>
<!--        <option value="linear_elastic_orthotropic">Linear elastic orthotropic</option> -->
<!--        <option value="hyperelastic_neohookean">Hyperelastic neo-hookean</option> -->
      </select>
     </div> 
    </div> 

    <div class="row mt-2 mb-1">
     <label for="text_name" class="col-2 col-form-label text-end"><?=$label["E="]?></label>
     <div class="col-4">
      <div class="input-group">
       <input type="text" class="form-control" name="E" id="text_E" value="<?=$E?>" onblur="ajax2problem(this.name, this.value)">
       <!-- TODO: choice -->
       <span class="input-group-text"><?=$label["GPa"]?></span>
      </div>
     </div>

     <label for="text_name" class="col-2 col-form-label text-end"><?=$label["nu="]?></label>
     <div class="col-4">

      <div class="input-group">
       <input type="text" class="form-control" name="nu" id="text_nu" value="<?=$nu?>" onblur="ajax2problem(this.name, this.value)">
       <button class="btn btn-light dropdown-toggle" type="button" id="button_dropdown_bc_<?=$i+1?>" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-three-dots"></i>
       </button>
       <ul class="dropdown-menu" aria-labelledby="button_dropdown_bc_<?=$i+1?>">
        <li>
         <a class="dropdown-item" href="#" onclick="fee_show()">
          <i class="bi bi-pencil-square me-2"></i>Edit full solver input
         </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
         <a class="dropdown-item" href="#">
          <i class="bi bi-question-circle me-2"></i>Help
         </a>
        </li>
       </ul>
      </div>
     </div>
    </div>

    <div class="row mt-2 mb-1">
     <label for="material_model" class="col-4 col-form-label text-end">Thermal expansion</label>
     <div class="col-8">
      <select class="form-select" id="material_model" onchange="">
       <option value="thermal_none">None</option>
<!--        <option value="thermal_isotropic">Isotropic</option> -->
<!--        <option value="thermal_orthotropic">Orthotropic</option> -->
      </select>
     </div> 
    </div> 
    
    <div class="row mt-2 mb-1">
     <label for="material_model" class="col-4 col-form-label text-end">Deformations</label>
     <div class="col-8">
      <select class="form-select" id="defromation_model" onchange="">
       <option value="small_deformation">Small (linear)</option>
<!--        <option value="large_deformation">Large (non-linear)</option> -->
      </select>
     </div> 
    </div> 
    

<!-- TODO: tip: this is the mean coefficient, edit the input to enter non-uniform coefficients -->
    <div class="row mt-2 mb-1 d-none">
     <label for="text_name" class="offset-2 col-2 col-form-label text-end"><?=$label["alpha="]?></label>
     <div class="col-6">
      <div class="input-group">
       <input type="text" class="form-control" name="alpha" id="text_alpha" value="<?=$alpha?>" onblur="ajax2problem(this.name, this.value)">
       <span class="input-group-text"><?=$label["1/K"]?></span>
       <button class="btn btn-light dropdown-toggle" type="button" id="button_dropdown_bc_<?=$i+1?>" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-three-dots"></i>
       </button>
       <ul class="dropdown-menu" aria-labelledby="button_dropdown_bc_<?=$i+1?>">
        <li>
         <a class="dropdown-item" href="#" onclick="fee_show()">
          <i class="bi bi-pencil-square me-2"></i>Edit full solver input
         </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
         <a class="dropdown-item" href="#">
          <i class="bi bi-question-circle me-2"></i>Help
         </a>
        </li>
       </ul>
      </div>
     </div>
    </div>
 
<?php
pop_accordion_item();
push_accordion_item("input", "problem", "Solver settings", false); 
?>
 
    <div class="row mt-2 mb-1">
     <label for="material_model" class="col-4 col-form-label text-end">Preconditioner</label>
     <div class="col-8">
      <select class="form-select" id="PC" onchange="ajax2problem(this.id, this.value);">
       <option value="default" <?=($pc == "default")?"selected":""?>>Default</option>
       <option value="gamg"    <?=($pc == "gamg")?"selected":""?>>GAMG</option>
       <option value="mumps"   <?=($pc == "mumps")?"selected":""?>>MUMPS</option>
      </select>
     </div> 
    </div> 
 
    <div class="row m-1 p-1">
     <div class="btn-group" role="group">
      <button class="btn btn-outline-primary w-100" onclick="fee_show()">
       <i class="bi bi-pencil-square me-2"></i>Show &amp; edit solver input
      </button>
      <button class="btn btn-outline-info">
       <i class="bi bi-question-circle"></i>
      </button>
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
