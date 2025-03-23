<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
?>

<script>
function upload_cad_file() {
  reset_error();
  div_progress.style.width = "0%";
  bootstrap_hide("cad_error");
  bootstrap_hide("cad_help");

  var files = cad.files;
  fileupload = new XMLHttpRequest();
  fileupload.onreadystatechange = function() {
    if (this.readyState == 4) {
      if (this.status == 200) {
        console.log(this.responseText);
        try {
          result = JSON.parse(this.responseText);
        } catch (e) {
          set_error(this.responseText);
          return false;
        }

        if (result["status"] != "ok") {
          set_error(result["error"]);
        }
        if (result["show_preview"]) {
          process_cad(result["cad_hash"]);
        }
      }
    }
  };

  // progress bar
  fileupload.upload.addEventListener("progress", function(e) {
    progress = parseInt(100 * e.loaded / e.total);
    div_progress.style.width = progress + "%";
  }, false);

  fileupload.open("POST", "import_cad.php", true);
  fileupload.setRequestHeader("X_FILENAME", files[0].name.replace(/[^a-zA-Z0-9\-]/gi, ''));
  fileupload.send(files[0]);
}
</script>

     <div class="mb-3">
      <label for="cad" class="form-label">
       <span class="badge text-bg-primary" id="badge_cad">1</span>&nbsp;CAD file in <a href="https://en.wikipedia.org/wiki/ISO_10303-21" target="_blank">STEP</a> format
      </label>
      <div id="cad_upload">
       <input class="form-control form-control-lg" style="height: 350px" id="cad" type="file" onchange="upload_cad_file()">
 
       <div id="cad_progress" class="progress mt-2 mb-2" role="progressbar">
        <div class="progress-bar bg-info" style="width: 0%" id="div_progress"></div>
       </div>
       <div id="cad_help" class="form-text">
        Pick or drag-and-drop a single-solid CAD file in STEP format.<br>
        If you do not have one, <a href="sample.step">download a sample STEP file here</a>.
       </div>
      </div>

      <div id="cad_preview" class="d-none">
       <x3d id="canvas" class="background-white w-100" height="400px"
        xmlns:xsd="http://www.w3.org/2001/XMLSchema-instance" version="3.3"
        xsd:noNamespaceSchemaLocation="http://www.web3d.org/specifications/x3d-3.3.xsd"
        showProgress="true"
        showStat="false"
        showLog="false">
        <scene>
         <OrthoViewpoint id="inline_viewpoint"></OrthoViewpoint>
         <inline id="inline_x3d" nameSpaceName="model" mapDEFToID="true"></inline>
        </scene>
       </x3d>
      </div>

      <div id="cad_error" class="invalid-feedback d-none"></div>
      <div id="cad_again" class="d-none">
       <button id="btn_again" class="btn btn-primary" onclick="choose_another_cad()">
        <i class="bi bi-arrow-left-circle"></i>&nbsp;Choose another CAD
       </button>
      </div>
     </div>
