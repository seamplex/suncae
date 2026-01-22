<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

include("ux.php");
include("labels.php");

title_left("Results");
push_accordion("results");
push_accordion_item("currentresults", "results", "Summary", true);

if ($has_results) {

  include("results/{$problem}.php");
} else if ($has_results_attempt) {
  if ($results_meta["status"] == "canceled") {
    suncae_log_error("problem {$id} canceled"); 
?>
    <div class="small alert alert-dismissible alert-warning">
     The solving process was canceled.
     <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div> 

    <button class="btn btn-lg btn-outline-success w-100" onclick="relaunch_solving('<?=$problem_hash?>')">
     <i class="bi bi-arrow-repeat mx-2"></i>&nbsp;Re-launch solver
    </button>
<?php
  } else if ($results_meta["status"] == "syntax_error") {
    suncae_log_error("problem {$id} syntax error: " . file_get_contents("{$case_dir}/run/{$problem_hash}-check.2"));     
?>
    <pre class="small alert alert-warning"><?=file_get_contents("{$case_dir}/run/{$problem_hash}-check.2")?></pre>
<?php
  } else if ($results_meta["status"] == "error") {
    if (file_exists("{$case_dir}/run/{$problem_hash}.2")) {
      suncae_log_error("problem {$id} error: " . file_get_contents("{$case_dir}/run/{$problem_hash}.2"));
?>
    <pre class="small alert alert-danger"><?=file_get_contents("{$case_dir}/run/{$problem_hash}.2")?></pre>
    <button class="btn btn-lg btn-outline-success w-100" onclick="relaunch_solving('<?=$problem_hash?>')">
     <i class="bi bi-arrow-repeat mx-2"></i>&nbsp;Re-launch solver
    </button>
<?php
    } else {
      suncae_log_error("problem {$id} got status error but not stderr");
?>
      Got status error but no stderr.
<?php
    }
  } else {
    suncae_log_error("problem {$id} unknown error");
?>
    Not sure what happened.
<?php
  }
  
} else  { 
    suncae_log_error("problem {$id} there are no results nor any attempt at getting them");
?>  
    <div class="small alert alert-dismissible alert-warning">
     There are no results nor any attempt at getting them.
    </div> 
<?php
}

if (file_exists("{$case_dir}/run/{$problem_hash}.vtk")) {
?>
    <div class="row m-1 p-1">
     <div class="btn-group" role="group">
      <a class="btn btn-success w-100" href="results_vtk.php?id=<?=$id?>&hash=<?=$problem_hash?>">
       <i class="bi bi-download me-2"></i>Download VTK
      </a>
<!--      
      <button class="btn btn-success">
       <i class="bi bi-question-circle"></i>
      </button>
-->
     </div>
    </div>
<?php  
}

pop_accordion_item();
push_accordion_item("fields", "results", "Fields", false);
?>
<h5>Not yet implemented</h5>
<?php
pop_accordion_item();


push_accordion_item("probe", "results", "Probe point", false);
?>
<h5>Not yet implemented</h5>
<?php
pop_accordion_item();

$console = "";
if (file_exists("{$case_dir}/run/{$problem_hash}.1")) {
  $console = shell_exec("grep -v ^[-=\\.]*$ {$case_dir}/run/{$problem_hash}.1");
}

if ($console != "") {
  push_accordion_item("console", "results", "Console output", false);
?>

    <div class="col-12">
     <div class="alert alert-light w-100 text-small m-0 p-0">
<pre id="mesh_log" class="small mx-1 mt-3 p-1">
<?=$console?>
</pre>
     </div>
<?php
  pop_accordion_item();
}
pop_accordion();
?>

<div class="d-grid mx-2 mt-4">
 <div class="btn-group w-100" role="group">
  <button class="btn w-25 btn-info" type="button" id="button_back" onclick="change_step(1)">
   <i class="bi bi-arrow-left-short mx-1"></i>
   Mesh
  </button>

  <button class="btn w-75 btn-secondary" type="button" id="button_next" onclick="change_step(2)">
   <i class="bi bi-arrow-left mx-2"></i>
   Problem
  </button>
 </div>
</div>

<div class="bg-white accordion-body mt-5 m-1 p-2 <?=($displ_max>0)?"":"d-none"?>">
 <div class="row">
  <div class="col-6">
   <input type="range" class="form-range mx-2" name="range_warp" id="range_warp" min="0" max="<?=$warp_max?>" value="0.5" step="<?=0.01*$warp_max?>" oninput="warp(this.value)">
  </div> 
  <div class="col-3">
   <input type="text" class="form-control" name="text_warp" id="text_warp" size="4" value="" oninput="warp(this.value)">
  </div>
  <div class="col-3">
   <button type="button" class="btn btn-secondary" onclick="real_warp()">Real&nbsp;warp</button>
  </div>
 </div> 
</div> 

<?php
if (isset($displ_max) && $displ_max > 0) {
?>
<img src onerror="animate_warp_auto(<?=$warp_max?>, 1000);"> 
<?php
} else {
?>
<img src onerror="warp_max=0;"> 
<?php
}
?>
