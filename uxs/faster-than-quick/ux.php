<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

function title_left($title = "") {
?>
  <h5 class="text-center"><?=$title?></h5>
  <hr>
<?php
}

function push_accordion($name = "") {
?>
<div class="accordion mb-2" id="accordion_<?=$name?>">
<?php 
}

function pop_accordion() {
?>
</div>
<?php  
}

// TODO: push parent name in a global var (a stack so nested accordions work)
function push_accordion_item($name = "", $parent = "", $title = "", $shown = false) {
?>
 <div class="accordion-item">
  <h2 class="accordion-header" id="heading_<?=$name?>">
   <button class="accordion-button <?=($shown)?"":"collapsed"?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?=$name?>" aria-expanded="<?=($shown)?"true":"false"?>" aria-controls="collapse_<?=$name?>">
    <?=$title?>
   </button>
  </h2>
  <div id="collapse_<?=$name?>" class="accordion-collapse collapse <?=($shown)?"show":"collapse"?>" aria-labelledby="heading_<?=$name?>" data-bs-parent="#accordion_<?=$parent?>">
   <div class="accordion-body p-1 pt-2">
<?php
}

function pop_accordion_item() {
?>
   </div> 
  </div> 
 </div>
<?php
}

function row_set_width($width) {
  global $row_width;
  $row_width = $width;
}

function row_ro($label, $value, $condition = true) {
  global $row_width;
?>
   <div class="row mb-1 mx-0">
    <div class="col-<?=$row_width?> text-end text-primary"><?=$label?></div>
    <div class="col-<?=(12-$row_width)?> <?=($condition == false) ? "text-danger" : "" ?>"><?=$value?></div>
   </div>
<?php
}

function row_form_ro($id, $label, $value) {
  global $row_width;
?>
   <div class="row mb-1 mx-0">
    <label for="text_<?=$id?>" class="col-<?=$row_width?> col-form-label text-end text-primary"><?=$label?></label>
    <div class="col-<?=12-$row_width?>">
     <input type="text" class="form-control-plaintext" id="text_<?=$id?>" value="<?=$value?>" readonly>
    </div> 
   </div> 
<?php
}

function row_ro_units($label, $value, $unit = "") {
  global $row_width;
?>
   <div class="row mb-1 mx-0">
    <label class="col-<?=$row_width?> col-form-label text-end text-primary"><?=$label?></label>
    <div class="col-<?=(12-$row_width)?>">
     <div class="input-group">
      <input type="text" class="form-control form-control-sm" value="<?=$value?>" readonly>
<?php
  if ($unit != "") {
?>
      <span class="input-group-text form-control-sm"><?=$unit?></span>
<?php
  }
?>
     </div>
    </div> 
   </div>
<?php
}


function row_form_code($id, $label, $value) {
  global $row_width;
?>
   <div class="row mb-1 mx-0">
    <label for="text_<?=$id?>" class="col-<?=$row_width?> col-form-label text-end text-primary"><?=$label?></label>
    <div class="col-<?=12-$row_width?>">
     <code class="form-control-plaintext mt-1"><?=$value?></code>
    </div> 
   </div> 
<?php
}

// TODO: onblur
function row_form($id, $label, $value) {
  global $row_width;
?>

   <div class="row mb-1 mx-0">
    <label for="text_<?=$id?>" class="col-<?=$row_width?> col-form-label text-end text-primary"><?=$label?></label>
    <div class="col-<?=12-$row_width?>">
     <input type="text" class="form-control" name="<?=$id?>" id="text_<?=$id?>" value="<?=$value?>" onblur="ajax2yaml(this.name, this.value)">
    </div>
   </div>

<?php
}

function row_form_range($variable, $label, $value, $visibility, $min, $max, $step, $on_input, $on_blur, $on_remove) {
?>  
    <div class="row mb-2 mx-0 d-<?=$visibility?>" id="row_<?=$variable?>">
     <label for="text_<?=$variable?>_value" class="col-6 col-form-label text-end">
      <?=$label?>
     </label>
     <div class="col-4">
      <input 
        type="text"
        class="form-control"
        name="mesh_<?=$variable?>"
        id="text_<?=$variable?>"
        value="<?=$value?>"
        oninput="<?=$on_input?>('<?=$variable?>', this.value)"
        onblur="<?=$on_blur?>('<?=$variable?>', this.value)"
      >
     </div>
     <div class="col-2">
      <div class="dropdown">
       <button class="btn btn-outline-danger dropdown-toggle" type="button" id="button_dropdown_remove_<?=$variable?>" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-trash"></i>
       </button>
       <ul class="dropdown-menu" aria-labelledby="button_dropdown_remove_<?=$variable?>">
        <li><a class="dropdown-item" href="#" onclick="<?=$on_remove?>('<?=$variable?>')">Remove setting</a></li>
       </ul>
      </div>     
     </div> 
     <div class="col-12">
      <input type="range" class="form-range" id="range_<?=$variable?>" oninput="<?=$on_input?>('<?=$variable?>', this.value)" onblur="<?=$on_blur?>('<?=$variable?>', this.value)" min="<?=$min?>" max="<?=$max?>" step="<?=$step?>" value="<?=$value?>">
     </div>
    </div>   
<?php 
}

function row_form_int($variable, $label, $value, $visibility, $min, $max, $step, $on_blur, $on_remove) {
?>  
    <div class="row mb-2 mx-0 d-<?=$visibility?>" id="row_<?=$variable?>">
     <label for="text_<?=$variable?>_value" class="col-6 col-form-label text-end">
      <?=$label?>
     </label>
     <div class="col-4">
      <input type="number" class="form-control"
        name="mesh_<?=$variable?>"
        id="text_<?=$variable?>"
        value="<?=$value?>"
        min="<?=$min?>"
        max="<?=$max?>"
        step="<?=$step?>"
        onchange="<?=$on_blur?>('<?=$variable?>', this.value)"
        onblur="<?=$on_blur?>('<?=$variable?>', this.value)"
      >
     </div>
     <div class="col-2">
      <div class="dropdown">
       <button class="btn btn-outline-danger dropdown-toggle" type="button" id="button_dropdown_remove_<?=$variable?>" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-trash"></i>
       </button>
       <ul class="dropdown-menu" aria-labelledby="button_dropdown_remove_<?=$variable?>">
        <li><a class="dropdown-item" href="#" onclick="<?=$on_remove?>('<?=$variable?>')">Remove setting</a></li>
       </ul>
      </div>     
     </div>
    </div> 
<?php 
}
?>
