<?php
include("../../solvers/common.php");
$default_physics = "solid";
$default_problem = "mechanical";
$default_solver = "feenox";
?>

<!doctype html>
<html lang="en" class="h-100">
<!--
 This file is part of SunCAE.
 SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
-->
<head>
 <meta charset="utf-8">
 <title>Faster-than-quick CAE</title>
 
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <link href="../css/faster-than-quick/bootstrap.min.css" rel="stylesheet">
 <link href="../css/faster-than-quick/bootstrap-icons.min.css" rel="stylesheet">
 <link href="../css/faster-than-quick/ftq.css" rel="stylesheet">
 <link href="../css/faster-than-quick/x3dom.css" rel="stylesheet">
<script>
var csrf_token = "<?=htmlspecialchars(suncae_csrf_token())?>";
var uploaded_cad_hash = "";
var uploaded_cad_solids = 0;
var preview_faces = 0;
var preview_solid_colors = [];
var preview_face_to_solids = {};

function preview_face_primary_solid(face_id) {
  if (preview_face_to_solids == null) {
    return 0;
  }
  if (typeof preview_face_to_solids[face_id] === "undefined") {
    return 0;
  }
  if (Array.isArray(preview_face_to_solids[face_id]) && preview_face_to_solids[face_id].length > 0) {
    return parseInt(preview_face_to_solids[face_id][0], 10);
  }
  return 0;
}

function preview_apply_solid_colors(try_count = 0) {
  if (uploaded_cad_solids <= 1 || preview_faces <= 0 || Object.keys(preview_face_to_solids).length == 0) {
    return;
  }

  let applied = 0;
  for (let face = 1; face <= preview_faces; face++) {
    let matface = document.getElementById("model__matface" + face);
    if (matface == null) {
      continue;
    }
    const solid_id = preview_face_primary_solid(face);
    if (solid_id > 0 && typeof preview_solid_colors[solid_id] !== "undefined") {
      const rgb = preview_solid_colors[solid_id];
      matface.diffuseColor = rgb[0] + " " + rgb[1] + " " + rgb[2];
      applied++;
    }
  }

  if (applied == 0 && try_count < 30) {
    setTimeout(function() { preview_apply_solid_colors(try_count + 1); }, 100);
  }
}

function bootstrap_hide(id) {
  document.getElementById(id).classList.remove("d-block");
  document.getElementById(id).classList.remove("d-inline");
  document.getElementById(id).classList.add("d-none");
}
function bootstrap_block(id) {
  document.getElementById(id).classList.add("d-block");
  document.getElementById(id).classList.remove("d-none");
}

function reset_error(message) {
  cad_error.innerHTML = "";
  bootstrap_hide("cad_error");
}

function set_error(message) {
  if (cad_error.innerHTML != "") {
    cad_error.innerHTML += "<br>";
  }
  cad_error.innerHTML += message;
  bootstrap_block("cad_error");
}

function selected_treatment_mode() {
  if (typeof select_treatment_mode === "undefined") {
    return "single_material";
  }
  return select_treatment_mode.value;
}

function update_treatment_controls(solids, effective_mode) {
  uploaded_cad_solids = solids;
  if (solids > 1) {
    bootstrap_block("div_treatment");
    div_treatment_help.innerHTML = "This CAD has " + solids + " solids. By default, SunCAE fuses solids that share the same material. Use the second option only when solids may require different materials.";
  } else {
    bootstrap_hide("div_treatment");
    select_treatment_mode.value = "single_material";
    if (solids == 1) {
      div_treatment_help.innerHTML = "Single-solid CAD detected. No extra geometry treatment is needed.";
    } else {
      div_treatment_help.innerHTML = "";
    }
  }
  if (effective_mode && effective_mode != "") {
    select_treatment_mode.value = effective_mode;
  }
}

function treatment_mode_change() {
  if (uploaded_cad_hash != "") {
    process_cad(uploaded_cad_hash);
  }
}


function process_cad(cad) {
  div_progress.classList.add("progress-bar-striped");
  div_progress.classList.add("progress-bar-animated");
  div_progress.innerHTML = "Processing CAD...";

  uploaded_cad_hash = cad;
  cad_hash.value = "";
  enable_btn_start();
  var treatment_mode = selected_treatment_mode();

  ajax = new XMLHttpRequest();
  ajax.onreadystatechange = function() {
    if (this.readyState == 4) {
      if (this.status == 200) {
        console.log(this.responseText);
        try {
          result = JSON.parse(this.responseText);
        } catch (e) {
          set_error(this.responseText);
          return false;
        }

        div_progress.classList.remove("progress-bar-striped");
        div_progress.classList.remove("progress-bar-animated");
        div_progress.innerHTML = "";

        if (result["status"] == "ok") {
          if (typeof result["original_solids"] !== "undefined") {
            update_treatment_controls(result["original_solids"], result["effective_mode"]);
          }
          if (typeof result["single_material_solids"] !== "undefined" && parseInt(result["single_material_solids"]) > 1) {
            div_disjoint_warning_text.innerHTML = "Warning: after fusing, the geometry still has " + result["single_material_solids"] + " disjoint solids. You have to set fixation (Dirichlet) boundary conditions in each separate set of solids.";
            bootstrap_block("div_disjoint_warning");
          } else {
            bootstrap_hide("div_disjoint_warning");
          }
          preview_faces = (typeof result["faces"] !== "undefined") ? parseInt(result["faces"]) : 0;
          preview_solid_colors = (typeof result["solid_colors"] !== "undefined") ? result["solid_colors"] : [];
          preview_face_to_solids = (typeof result["face_to_solids"] !== "undefined") ? result["face_to_solids"] : {};
          var processed_hash = (typeof result["cad_hash"] !== "undefined") ? result["cad_hash"] : cad;
          show_preview(processed_hash, result["position"], result["orientation"], result["centerOfRotation"], result["fieldOfView"]);
        } else {
          if (typeof result["original_solids"] !== "undefined") {
            update_treatment_controls(result["original_solids"], "single_material");
          }
          bootstrap_hide("div_disjoint_warning");
          cad_hash.value = "";
          enable_btn_start();
          set_error(result["error"]);
        }
      }
    }
  };

  ajax.open("POST", "./process.php", true);
  ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  ajax.send("csrf_token=" + encodeURIComponent(csrf_token) + "&cad_hash=" + encodeURIComponent(cad) + "&treatment_mode=" + encodeURIComponent(treatment_mode));

}

function show_preview(md5_sum, position, orientation, centerOfRotation, fieldOfView) {
  bootstrap_hide("cad_upload");
  bootstrap_block("cad_preview");
  bootstrap_block("cad_again");

  inline_x3d.setAttribute("url", "preview.php?id=" + md5_sum);
  inline_viewpoint.setAttribute("position", position);
  inline_viewpoint.setAttribute("orientation", orientation);
  inline_viewpoint.setAttribute("centerOfRotation", centerOfRotation);
  inline_viewpoint.setAttribute("fieldOfView", fieldOfView);

  cad_hash.value = md5_sum;
  badge_cad.classList.remove("text-bg-primary");
  badge_cad.classList.add("text-bg-success");

  setTimeout(function() { canvas.runtime.fitAll(); }, 1000);
  preview_apply_solid_colors(0);

  enable_btn_start();
}

function choose_another_cad() {
  reset_error();
  uploaded_cad_hash = "";
  uploaded_cad_solids = 0;
  preview_faces = 0;
  preview_solid_colors = [];
  preview_face_to_solids = {};
  cad_hash.value = "";
  select_treatment_mode.value = "single_material";
  bootstrap_hide("div_treatment");
  div_treatment_help.innerHTML = "";
  bootstrap_hide("div_disjoint_warning");
  bootstrap_hide("cad_preview");
  bootstrap_hide("cad_again");
  bootstrap_block("cad_upload");
  badge_cad.classList.add("text-bg-primary");
  badge_cad.classList.remove("text-bg-success");
  enable_btn_start();
}

function update_problem(what, old, update) {

  ajax = new XMLHttpRequest();
  ajax.onreadystatechange = function() {
    if (this.readyState == 4) {
      if (this.status == 200) {
        console.log(what);
        console.log(update);
        console.log(this.responseText);
        try {
          result = JSON.parse(this.responseText);
        } catch (e) {
          set_error(this.responseText);
          return false;
        }

        var combo_old = document.getElementById(old);
        var badge = document.getElementById("badge_"+old);
        if (combo_old.value != "none" && combo_old.value != "") {
          combo_old.classList.remove("is-invalid");
          badge.classList.add("text-bg-success");
          badge.classList.remove("text-bg-primary");
        } else {
          if (update != "physics") {
            combo_old.classList.add("is-invalid");
          } else {
            combo_old.classList.remove("is-invalid");
          }
          badge.classList.add("text-bg-primary");
          badge.classList.remove("text-bg-success");
        }

        var combo_new = document.getElementById(update);
        while (combo_new.options.length > 0) {
          combo_new.remove(0);
        }
        for (let i = 0; i < result["keys"].length; i++) {
          combo_new.add(new Option(result["values"][i], result["keys"][i]));
          console.log(result["keys"][i] + " " + result["values"][i]);
        }

        if (update == "solver") {
          update_problem("feenox", "solver", "mesher");
          badge_mesher.classList.remove("text-bg-primary");
          badge_mesher.classList.add("text-bg-success");
        }

        enable_btn_start();

      }
    }
  };
  ajax.open("GET", "./problems.php?what=" + what, true);
  ajax.send();
}

function enable_btn_start() {

  if (physics.value != "none" && problem.value != "none" && solver.value != "dummy") {
    bootstrap_hide("div_unsupported");
    if (cad_error.innerHTML == "" && cad_hash.value != "") {
      btn_start.disabled = false;
      btn_start.classList.add("btn-success");
      btn_start.classList.remove("btn-primary");
    } else {
      btn_start.disabled = true;
      btn_start.classList.add("btn-primary");
      btn_start.classList.remove("btn-success");
    }
  } else {
    btn_start.disabled = true;
    btn_start.classList.add("btn-primary");
    btn_start.classList.remove("btn-success");
    if (physics.value != "none" && problem.value != "none") {
      bootstrap_block("div_unsupported");
    } else {
      bootstrap_hide("div_unsupported");
    }
  }
}


</script>
</head>
<body>

<?php
include("about.php");
?>

 <main>
  <div class="container">
   <header class="d-flex flex-wrap justify-content-center py-3 mb-4 border-bottom">
    <a href="https://github.com/seamplex/suncae" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none" target="_blank">
     <span class="fs-4">Faster-than-quick @ <span class="text-secondary">Sun</span><span class="text-primary">CAE</span></span>
    </a>

    <ul class="nav nav-pills">
     <li class="nav-item dropdown"><a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Help</a>
      <ul class="dropdown-menu text-small">      
       <li><a class="dropdown-item" href="https://github.com/seamplex/suncae">What's this?</a></li>
       <li><a class="dropdown-item" href="https://github.com/seamplex/suncae/blob/main/doc/FAQs.md">FAQs</a></li>
       <li><a class="dropdown-item" href="https://github.com/seamplex/suncae/blob/main/doc/tutorials.md">Tutorials</a></li>
       <li><hr class="dropdown-divider"></li>
       <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modal_about">About</a></li>
      </ul>
     </li>
     <!-- TODO: profile in auth -->
     <li class="nav-item"><a href="#" class="nav-link"><?=$username?></a></li>
    </ul>
   </header>
  </div>

  <form action="create.php" method="post">
   <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(suncae_csrf_token())?>">
   <div class="container">
    <div class="row">
     <div class="col-lg-6">
      <?php include("importers/{$cadimporter}.php"); ?>
     </div>

     <div class="col-lg-6">

<!--
      <div class="col mb-3">
       <label for="name" class="form-label">Case name</label>
       <input type="text" class="form-control" name="name" id="name" placeholder="Test">
       <div id="text_help" class="form-text">
        Do not worry about the name, you can change it later.
       </div>
      </div>
-->

      <div class="row mb-3">
       <div class="col-lg-6">
        <label for="physics" class="form-label">
         <span class="badge text-bg-success" id="badge_physics">2</span>&nbsp;Physics
        </label>
        <select class="form-select col-6" id="physics" onchange="update_problem(this.value, 'physics', 'problem')" required>
<?php
  foreach ($problems as $physics => $problem) {
?>
         <option value="<?=$physics?>" <?=($physics == $default_physics) ? "selected" : ""?>><?=$physics_name[$physics]?></option>
<?php
  }
?>
        </select>
<!--        
        <div id="physics_help" class="form-text mb-3">
         First pick the physics, then choose the problem.
        </div>
-->
       </div>

       <div class="col-lg-6">
        <label for="problem" class="form-label">
         <span class="badge text-bg-success" id="badge_problem">3</span>&nbsp;Problem
        </label>
        <select class="form-select col-6" id="problem" name="problem" onchange="update_problem(this.value, 'problem', 'solver')">

<?php
  $keys = array();
  foreach ($problems[$default_physics] as $index => $problem) {
    $keys[$problem] = $problem_name[$problem];
  }
  foreach ($keys as $key => $value) {
?>
         <option value="<?=$key?>" <?=($key == $default_problem) ? "selected" : ""?>><?=$value?></option>
<?php
  }
?>
        </select>
<!--        
        <div id="problem_help" class="form-text mb-3">
         Once you have the problem, choose the solver.
        </div>
-->
       </div>
      </div> 

      <div class="row mb-3">
       <div class="col-lg-6">
        <label for="solver" class="form-label">
         <span class="badge text-bg-success" id="badge_solver">4</span>&nbsp;Solver
        </label>
        <select class="form-select col-6" id="solver" name="solver" onchange="update_problem(this.value, 'solver', 'mesher')">
<?php
  $keys = array();
  foreach ($solvers[$default_problem] as $index => $solver) {
    $keys[$solver] = $solvers_names[$solver];
  }
  foreach ($keys as $key => $value) {
?>
         <option value="<?=$key?>" <?=($key == $default_solver) ? "selected" : ""?>><?=$value?></option>
<?php
  }
?>
        </select>
<!--
        <div id="solver_help" class="form-text">
         This PoC supports only <a href="https://www.seamplex.com/feenox" target="_blank">FeenoX</a>.
        </div>
-->
       </div> 

       <div class="col-lg-6">
        <label for="mesher" class="form-label">
         <span class="badge text-bg-success" id="badge_mesher">4</span>&nbsp;Mesher
        </label>
        <select class="form-select col-6" id="mesher" name="mesher" onchange="enable_btn_start()">
         <option value="gmsh" selected>Gmsh</option>
        </select>
<!--
        <div id="mesher_help" class="form-text">
         This PoC supports only <a href="http://gmsh.info/" target="_blank">Gmsh</a>.
        </div>
-->
       </div>
      </div>

      <div class="row mb-3 d-none" id="div_treatment">
       <div class="col-12">
        <label for="select_treatment_mode" class="form-label">
         <span class="badge text-bg-success" id="badge_treatment">5</span>&nbsp;Geometry treatment
        </label>
        <select class="form-select" id="select_treatment_mode" onchange="treatment_mode_change()">
         <option value="single_material" selected>All solids have the same material (fuse)</option>
         <option value="multi_material">Solids may have different materials (conformal interfaces)</option>
        </select>
        <div id="div_treatment_help" class="form-text"></div>
       </div>
      </div>

      <div class="row mb-3 d-none" id="div_disjoint_warning">
       <div class="col-12">
        <div class="alert alert-warning mb-0" id="div_disjoint_warning_text"></div>
       </div>
      </div>

      <div class="row mt-4 mb-3">
       <div class="d-grid gap-2 col-lg-6 mx-auto mt-3">
        <input type="hidden" id="cad_hash" name="cad_hash" value="">
        <button id="btn_start" class="btn btn-lg btn-primary" disabled type="submit">
         <i class="bi bi-cloud-upload"></i>&nbsp;Start
        </button>
       </div>
      </div>

      <div class="row mt-3">
       <div class="col alert alert-warning d-none" id="div_unsupported">
        The selected problem is not yet available.
        <a href="#" class="alert-link">Contact us</a> for more information.
       </div>
      </div>
     </div> 
    </div> 
   </div>
  </form> 
 </main>

 <footer class="py-5">
  <div class="container">

   <div class="row">
    <div class="col-6 col-md-2 mb-3">
     <h5>SunCAE</h5>
     <ul class="nav flex-column">
      <li class="nav-item mb-2"><a class="nav-link p-0 text-body-secondary" href="https://caeplex.com/suncae">Live demo</a></li>
      <li class="nav-item mb-2"><a class="nav-link p-0 text-body-secondary" href="https://github.com/seamplex/suncae/tree/main?tab=readme-ov-file#how">Quick start</a></li>
      <li class="nav-item mb-2"><a class="nav-link p-0 text-body-secondary" href="https://github.com/seamplex/suncae">Source</a></li>
      <li class="nav-item mb-2"><a class="nav-link p-0 text-body-secondary" href="https://github.com/seamplex/suncae/tree/main?tab=readme-ov-file#licensing">Licensing</a></li>
     </ul>
    </div>

    <div class="col-6 col-md-2 mb-3">
     <h5>Support</h5>
     <ul class="nav flex-column">
      <li class="nav-item mb-2"><a class="nav-link p-0 text-body-secondary" href="https://github.com/seamplex/suncae/blob/main/doc/FAQs.md">FAQs</a></li>
      <li class="nav-item mb-2"><a class="nav-link p-0 text-body-secondary" href="https://github.com/seamplex/suncae/discussions">Forum</a></li>
      <li class="nav-item mb-2"><a class="nav-link p-0 text-body-secondary" href="https://github.com/seamplex/suncae/blob/main/doc/tutorials.md">Tutorials</a></li>
      <li class="nav-item mb-2"><a class="nav-link p-0 text-body-secondary" href="https://github.com/seamplex/suncae/tree/main/doc">Documentation</a></li>
     </ul>
    </div>

    <div class="col-6 col-md-2 mb-3">
    <h5>News</h5>
     <ul class="nav flex-column">
      <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-body-secondary">Releases</a></li>
      <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-body-secondary">Blog</a></li>
     </ul>
    <h5>Contact</h5>
     <ul class="nav flex-column">
      <li class="nav-item mb-2"><a class="nav-link p-0 text-body-secondary" href="https://www.linkedin.com/in/jeremytheler">LinkedIn</a></li>
     </ul>
     
    </div>
<!--
    <div class="col-md-5 offset-md-1 mb-3">
     <form>
      <h5>Subscribe to our newsletter</h5>
      <p>Monthly digest of what's new and exciting from us.</p>
      <div class="d-flex flex-column flex-sm-row w-100 gap-2">
       <label for="newsletter1" class="visually-hidden">Email address</label>
       <input id="newsletter1" type="text" class="form-control" placeholder="Email address">
       <button class="btn btn-primary" type="button">Subscribe</button>
      </div>
     </form>
    </div>
-->
   </div>


   <div class="d-flex flex-column flex-sm-row justify-content-between py-4 my-4 border-top">
    <p>
     &copy; 2025 <a href="https://www.seamplex.com" target="_blank">Seamplex</a>.
     <a href="https://www.seamplex.com/suncae" target="_blank">SunCAE</a> is licensed under the
     <a href="https://www.gnu.org/licenses/agpl-3.0.en.html" target="_blank">GNU AGPL.</a>
     You can get the source code from <a href="https://github.com/seamplex/suncae">Github</a>.
    </p>
    <ul class="list-unstyled d-flex">
     <li class="ms-3"><a class="text-primary link-body-emphasis" href="#"><i class="bi bi-linkedin"></i></a></li>
     <li class="ms-3"><a class="text-primary link-body-emphasis" href="#"><i class="bi bi-youtube"></i></li>
     <li class="ms-3"><a class="text-primary link-body-emphasis" href="#"><i class="bi bi-github"></i></a></li>
    </ul>
   </div>
  </div>
 </footer>

<script type="text/javascript" src="../js/faster-than-quick/bootstrap.bundle.min.js"></script>
<script type="text/javascript" src="../js/faster-than-quick/x3dom.js"></script>

</body>
</html>


