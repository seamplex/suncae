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


function process_cad(cad) {
  div_progress.classList.add("progress-bar-striped");
  div_progress.classList.add("progress-bar-animated");
  div_progress.innerHTML = "Processing CAD...";

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
          show_preview(cad, result["position"], result["orientation"], result["centerOfRotation"], result["fieldOfView"]);
        } else {
          set_error(result["error"]);
        }
      }
    }
  };

  ajax.open("GET", "./process.php?cad_hash=" + cad, true);
  ajax.send();

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

  enable_btn_start();
}

function choose_another_cad() {
  reset_error();
  cad_hash.value = "";
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
<body onload="update_problem('physics', 'physics', 'physics')">

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
       <li><a class="dropdown-item" href="#">FAQs</a></li>
       <li><a class="dropdown-item" href="#">Tutorials</a></li>
       <li><hr class="dropdown-divider"></li>
       <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modal_about">About</a></li>
      </ul>
     </li>
     <!-- TODO: profile in auth -->
     <li class="nav-item"><a href="#" class="nav-link"><?=$username?></a></li>
    </ul>
   </header>
  </div>

  <div class="container">
   <div class="row">
    <div class="col-lg-6">
     <?php include("importers/{$cadimporter}.php"); ?>
    </div>

    <div class="col-lg-6">
     <form action="create.php" method="post">
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
         <span class="badge text-bg-primary" id="badge_physics">2</span>&nbsp;Physics
        </label>
        <select class="form-select col-6" id="physics" onchange="update_problem(this.value, 'physics', 'problem')" required>
        </select>
        <div id="physics_help" class="form-text mb-3">
         First pick the physics, then choose the problem.
        </div>
       </div>

       <div class="col-lg-6">
        <label for="problem" class="form-label">
         <span class="badge text-bg-primary" id="badge_problem">3</span>&nbsp;Problem
        </label>
        <select class="form-select col-6" id="problem" name="problem" onchange="update_problem(this.value, 'problem', 'solver')">
        </select>
        <div id="problem_help" class="form-text mb-3">
         Once you have the problem, choose the solver.
        </div>
       </div>
      </div> 

      <div class="row mb-3">

       <div class="col-lg-6">
        <label for="solver" class="form-label">
         <span class="badge text-bg-primary" id="badge_solver">4</span>&nbsp;Solver
        </label>
        <select class="form-select col-6" id="solver" name="solver" onchange="update_problem(this.value, 'solver', 'mesher')">
        </select>
<!--
        <div id="solver_help" class="form-text">
         This PoC supports only <a href="https://www.seamplex.com/feenox" target="_blank">FeenoX</a>.
        </div>
-->
       </div> 

       <div class="col-lg-6">
        <label for="mesher" class="form-label">
         <span class="badge text-bg-primary" id="badge_mesher">5</span>&nbsp;Mesher
        </label>
        <select class="form-select col-6" id="mesher" name="mesher" onchange="enable_btn_start()">
        </select>
<!--
        <div id="mesher_help" class="form-text">
         This PoC supports only <a href="http://gmsh.info/" target="_blank">Gmsh</a>.
        </div>
-->
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
     </form>
    </div> 
   </div> 
  </div>
 </main>

 <footer class="py-5">
  <div class="container">

   <div class="row">
    <div class="col-6 col-md-2 mb-3">
     <h5>SunCAE</h5>
     <ul class="nav flex-column">
      <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-body-secondary">Live demo</a></li>
      <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-body-secondary">Quick start</a></li>
      <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-body-secondary">Source</a></li>
      <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-body-secondary">Licensing</a></li>
      <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-body-secondary">Roadmap</a></li>
     </ul>
    </div>

    <div class="col-6 col-md-2 mb-3">
     <h5>Support</h5>
     <ul class="nav flex-column">
      <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-body-secondary">FAQs</a></li>
      <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-body-secondary">Forum</a></li>
      <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-body-secondary">Tutorials</a></li>
      <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-body-secondary">Documentation</a></li>
      <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-body-secondary">Solvers &amp; meshers</a></li>
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
      <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-body-secondary">LinkedIn</a></li>
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


