<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

include("ux.php");

$history_entries = suncae_case_run_history($case_dir, $mesher, $solver);

function history_h($value) {
  return htmlspecialchars(strval($value), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}
?>

<h5 class="text-center">Run history</h5>
<hr>

<div class="accordion" id="accordion_history">
 <div class="accordion-item">
  <h2 class="accordion-header" id="heading_run_history">
   <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_run_history" aria-expanded="true" aria-controls="collapse_run_history">
    Recent attempts
   </button>
  </h2>
  <div id="collapse_run_history" class="accordion-collapse collapse show" aria-labelledby="heading_run_history" data-bs-parent="#accordion_history">
   <div class="accordion-body p-2">
<?php if (count($history_entries) == 0) { ?>
    <div class="text-muted small">No mesh or solve attempts yet.</div>
<?php } else { ?>
    <div class="list-group list-group-flush">
<?php foreach ($history_entries as $entry) { ?>
     <div class="list-group-item px-0">
      <div class="d-flex align-items-center justify-content-between gap-2">
       <div>
        <div class="fw-semibold text-capitalize"><?=history_h($entry["kind"])?></div>
        <div class="small text-muted"><?=history_h($entry["tool"])?> · <?=history_h(substr($entry["hash"], 0, 8))?></div>
       </div>
       <span class="badge <?=($entry["status"] == "success") ? "bg-success" : (($entry["status"] == "running") ? "bg-info text-dark" : (($entry["status"] == "canceled" || $entry["status"] == "not_running") ? "bg-warning text-dark" : "bg-danger"))?>"><?=history_h($entry["status"])?></span>
      </div>
      <div class="small mt-2">
       <div><?=($entry["started_at"] != "") ? history_h(date("Y-m-d H:i", strtotime($entry["started_at"]))) : "Unknown start"?> · <?=history_h(suncae_elapsed_label($entry["elapsed_seconds"]))?></div>
<?php if ($entry["phase_label"] != "") { ?>
       <div class="text-muted"><?=history_h($entry["phase_label"])?></div>
<?php } ?>
<?php if ($entry["summary"] != "") { ?>
       <div class="text-muted"><?=history_h($entry["summary"])?></div>
<?php } ?>
      </div>
     </div>
<?php } ?>
    </div>
<?php } ?>
   </div>
  </div>
 </div>
</div>