<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

include("ux.php");
?>

<h5 class="text-center">Solving progress</h5>
<hr>

<div class="accordion" id="accordion_solving">

 <div class="accordion-item">
  <h2 class="accordion-header" id="heading_solvingstatus">
   <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_solvingstatus" aria-expanded="true" aria-controls="collapse_solvingstatus">
   Status
   </button>
  </h2>
  <div id="collapse_solvingstatus" class="accordion-collapse collapse show" aria-labelledby="heading_solvingstatus" data-bs-parent="#accordion_mesh">
   <div class="accordion-body">

<!--  TODO: the php in charge of informing the progress should also send the legends  -->

    <legend class="<?=$problem=="mechanical"?"":"d-none"?>">Second-order mesh</legend>
    <div class="progress mt-2 mb-4 <?=$problem=="mechanical"?"":"d-none"?>" role="progressbar">
     <div class="progress-bar bg-info" style="width=0%" id="progress_mesh"></div>
    </div>

    <legend>Build</legend>
    <div class="progress mt-2 mb-4" role="progressbar">
     <div class="progress-bar bg-info" style="width=0%" id="progress_build"></div>
    </div>

    <legend>Solve</legend>
    <div class="progress mt-2 mb-4" role="progressbar">
     <div class="progress-bar bg-info" style="width=0%" id="progress_solve"></div>
    </div>
    
    <legend>Compute fluxes</legend>
    <div class="progress mt-2 mb-4" role="progressbar">
     <div class="progress-bar bg-info" style="width=0%" id="progress_post"></div>
    </div>

<!--    
    <legend>Process</legend>
    <div class="progress mt-2 mb-4" role="progressbar">
     <div class="progress-bar bg-info" style="width=0%" id="progress_data"></div>
    </div>
-->
    <div class="col-12 my-4">
     <div class="d-flex justify-content-center">
      <div class="spinner-border text-primary" role="status">
       <span class="visually-hidden">Solving...</span>
      </div>
     </div> 
    </div>
    
    <div class="col-12 mt-3 py-2">
     <button class="btn btn-lg btn-outline-danger w-100" onclick="cancel_solving('<?=$problem_hash?>')">
      <i class="fa fa-fw fa-ban"></i>&nbsp;Cancel solving
     </button>
     
    </div>
  
   </div>
  </div>
 </div>

<img src onerror="update_problem_status('<?=$problem_hash?>')"> 
 
</div>
