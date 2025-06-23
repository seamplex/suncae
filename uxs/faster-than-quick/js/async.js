// TODO: understand when to update
async function update_mesh(mesh_hash = "") {
  let url = "mesh_data.php?id=" + id + (mesh_hash ? "&mesh=" + mesh_hash : "");
  try {
    let response = await fetch(url);
    if (!response.ok) throw new Error('Network response was not ok');
    let data = await response.json();

    nodes.setAttribute("point", data["nodes"]);
    surfaces_edges_set.setAttribute("coordIndex", data["surfaces_edges_set"]);

    let faces_html = "";
    results_indexedfaceset_set = "";
    for (const [key, value] of Object.entries(data["surfaces_faces_set"])) {
      // convert from IndexedTriangleSet to IndexedFaceSet
      let array = value.split(" ");
      for (let i = 0; i < array.length; i += 3) {
        results_indexedfaceset_set += array[i+0] + " " + array[i+1] + " " + array[i+2] + " " + array[i+0] + " -1 ";
      }

      // TODO: real bc colors
      faces_html += '<Shape><Appearance><Material diffuseColor="' + color["base"][0] + ' ' + color["base"][1] + ' ' + color["base"][2] +  '"></Material></Appearance><IndexedTriangleSet normalPerVertex="false" solid="false" index="' + value + '"><Coordinate use="nodes"></Coordinate></IndexedTriangleSet></Shape>';
    }
    surfaces_faces.innerHTML = faces_html;
    if (mesh_hash != "") current_mesh = mesh_hash;
    return true;
  } catch (exception) {
    set_error("Failed to load mesh data: " + exception);
    theseus_log(exception);
    return false;
  }
  
  return true;
}

// TODO: return true or false
async function update_results(problem_hash = "") {

  // TODO: check if we need to pull the data or we can use what we already have
  try {
    let response = await fetch("results_data.php?id="+id);
    if (!response.ok) throw new Error("Network response was not ok");
    let data = await response.json();


    if (data["error"] === undefined || data["error"] == "") {

      let coord_indexes = surfaces_edges_set.getAttribute("coordIndex");
      let nodes_val = document.getElementById("nodes").getAttribute("point");
      if (data["nodes_warped"] !== undefined) {
        results_surfaces_edges.innerHTML = '\
<Appearance><Material emissiveColor="0 0 0" diffuseColor="0 0 0"></Material></Appearance>\
<IndexedLineSet coordIndex="' + coord_indexes + '"><Coordinate id="nodes_warped"></Coordinate></IndexedLineSet>\
<ScalarInterpolator id="si" key="0 1" keyValue="0 1"><ScalarInterpolator>\
<CoordinateInterpolator id="ci" key="0 1" keyValue="' + nodes_val + ' ' + data["nodes_warped"] + '"></CoordinateInterpolator>\
<Route fromNode="ci" fromField="value_changed" toNode="nodes_warped" toField="point"></Route>\
<Route fromNode="si" fromField="value_changed" toNode="ci" toField="set_fraction"></Route>';
        si.setAttribute("set_fraction", "0");
      } else {
        results_surfaces_edges.innerHTML = '\
<Appearance><Material emissiveColor="0 0 0" diffuseColor="0 0 0"></Material></Appearance>\
<IndexedLineSet coordIndex="' + coord_indexes + '"><Coordinate use="nodes"></Coordinate></IndexedLineSet>';
      }

      let color_string = "";
      let array = data["field"].trim().split(" ");
      // TODO: read the field name from the ajax
      if (problem == "mechanical") {
        for (let i = 0; i < array.length; i++) {
          color_string += palette(array[i], "sigma") + ", ";
        }
      } else if (problem == "heat_conduction") {
        for (let i = 0; i < array.length; i++) {
          color_string += palette(array[i], "temperature") + ", ";
        }
      }        

      // TODO: improve
      if (problem == "mechanical") {
        coords_use = "nodes_warped";
      } else {
        coords_use = "nodes";
      }
      results_surfaces_faces.innerHTML = '\
<appearance><Material shininess="0.1"></Material></appearance>\
<IndexedFaceSet colorPerVertex="true" normalPerVertex="false" solid="false" coordIndex="' + results_indexedfaceset_set + '">\
<Coordinate use="' + coords_use + '"></Coordinate>\
<Color id="color_scalar" color="' + color_string + '"></Color>\
</IndexedFaceSet>';
    
      if (problem_hash != "") {
        current_results = problem_hash;
      }
    } else {
      set_error(data["error"]);
    }
  } catch (exception) {
    set_error("Failed to load results: " + exception);
    theseus_log(exception);
  }
}

async function ajax2yaml(field, value) {
  theseus_log("ajax2yaml("+field+","+value+")");
  let response;
  try {
    let res = await fetch("ajax2yaml.php?id="+id+"&field="+encodeURIComponent(field)+"&value="+encodeURIComponent(value));
    if (!res.ok) throw new Error("Network error");
    response = await res.json();
  } catch (exception) {
    set_error("Error 1, see console.");
    theseus_log(exception);
    return;
  }

  set_warning((response["warning"] === undefined) ? "" : response["warning"]);
  set_error((response["error"] === undefined) ? "" : response["error"]);
  if (response["content_id"] !== undefined && response["content_html"] !== undefined) {
    for (let i = 0; i < response["content_id"].length; i++) {
      document.getElementById(response["content_id"][i]).innerHTML = response["content_html"][i];
    }
  }
  if (response["hide"] !== undefined) {
    for (let i = 0; i < response["hide"].length; i++) bootstrap_hide(response["hide"][i]);
  }
  if (response["block"] !== undefined) {
    for (let i = 0; i < response["block"].length; i++) bootstrap_block(response["block"][i]);
  }
  if (response["inline"] !== undefined) {
    for (let i = 0; i < response["inline"].length; i++) bootstrap_inline(response["inline"][i]);
  }
  theseus_log(response);
}

// TODO: unify
async function ajax2problem(field, value) {
  theseus_log("ajax2problem("+field+","+value+")");

  let response;
  try {
    let res = await fetch("ajax2problem.php?id="+id+"&field="+encodeURIComponent(field)+"&value="+encodeURIComponent(value));
    if (!res.ok) throw new Error("Network error");
    response = await res.json();
  } catch (exception) {
    set_error("Error 2, see console.");
    theseus_log(exception);
    return;
  }

  set_warning((response["warning"] === undefined) ? "" : response["warning"]);
  set_error((response["error"] === undefined) ? "" : response["error"]);
  if (response["content_id"] !== undefined && response["content_html"] !== undefined) {
    for (let i = 0; i < response["content_id"].length; i++) {
      document.getElementById(response["content_id"][i]).innerHTML = response["content_html"][i];
    }
  }
  if (response["hide"] !== undefined) {
    for (let i = 0; i < response["hide"].length; i++) bootstrap_hide(response["hide"][i]);
  }
  if (response["block"] !== undefined) {
    for (let i = 0; i < response["block"].length; i++) bootstrap_block(response["block"][i]);
  }
  if (response["inline"] !== undefined) {
    for (let i = 0; i < response["inline"].length; i++) bootstrap_inline(response["inline"][i]);
  }
  theseus_log(response);
}

// TODO: unify, this is the same as above with different url
async function ajax2mesh(field, value) {
  theseus_log("ajax2mesh("+field+","+value+")");

  let response;
  try {
    let res = await fetch("ajax2mesh.php?id="+id+"&field="+encodeURIComponent(field)+"&value="+encodeURIComponent(value));
    if (!res.ok) throw new Error("Network error");
    response = await res.json();
  } catch (exception) {
    set_error("Error 3, see console.");
    theseus_log(exception);
    return;
  }

  set_warning((response["warning"] === undefined) ? "" : response["warning"]);
  set_error((response["error"] === undefined) ? "" : response["error"]);
  if (response["content_id"] !== undefined && response["content_html"] !== undefined) {
    for (let i = 0; i < response["content_id"].length; i++) {
      document.getElementById(response["content_id"][i]).innerHTML = response["content_html"][i];
    }
  }
  if (response["hide"] !== undefined) {
    for (let i = 0; i < response["hide"].length; i++) bootstrap_hide(response["hide"][i]);
  }
  if (response["block"] !== undefined) {
    for (let i = 0; i < response["block"].length; i++) bootstrap_block(response["block"][i]);
  }
  if (response["inline"] !== undefined) {
    for (let i = 0; i < response["inline"].length; i++) bootstrap_inline(response["inline"][i]);
  }
  theseus_log(response);
}

// ajax_change_step: this function has multiple XHRs, all replaced with fetch
/*
async function ajax_change_step() {
  html_leftcol.removeEventListener("hidden.bs.collapse", wrapper_leftcol_collape);
  let ajax_step;
  try {
    let res = await fetch("change_step.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + id + "&next_step=" + next_step + "&current_step=" + current_step
    });
    if (!res.ok) throw new Error("Network error");
    ajax_step = await res.json();
    theseus_log(ajax_step);
  } catch (exception) {
    theseus_log(exception);
    html_leftcol.innerHTML = '<div class="alert alert-dismissible alert-danger">' + exception + "</div>";
    set_error(exception);
    bs_loading.hide();
    return;
  }

  if (ajax_step.url !== undefined && ajax_step.step !== undefined) {
    try {
      let res = await fetch(ajax_step.url);
      if (!res.ok) throw new Error(ajax_step.url + ": " + res.statusText);
      let html = await res.text();
      html_leftcol.innerHTML = html;
    } catch (e) {
      set_error(e);
    }
    try {
      set_current_step(ajax_step);
    } catch (exception) {
      theseus_log(exception);
      html_leftcol.innerHTML = '<div class="alert alert-dismissible alert-danger">Error 4, see console.</div>';
      set_error("Error 4, see console.");
      return;
    }
  } else if (ajax_step.error !== undefined) {
    html_leftcol.innerHTML = '<div class="alert alert-dismissible alert-danger">' +  ajax_step.error + "</div>";
    set_error(ajax_step.error)
  } else {
    html_leftcol.innerHTML = '<div class="alert alert-dismissible alert-danger">Unknown response: ' +  JSON.stringify(ajax_step) + "</div>";
    set_error('Unknown response: ' +  JSON.stringify(ajax_step))
  }
  bs_loading.hide();
}
*/
// ajax_change_step: this function has multiple XHRs, all replaced with fetch
async function ajax_change_step() {
  html_leftcol.removeEventListener("hidden.bs.collapse", wrapper_leftcol_collape);
  let ajax_step;
  try {
    let res = await fetch("change_step.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + id + "&next_step=" + next_step + "&current_step=" + current_step
    });
    if (!res.ok) throw new Error("Network error");
    ajax_step = await res.json();
    theseus_log(ajax_step);
  } catch (exception) {
    theseus_log(exception);
    html_leftcol.innerHTML = '<div class="alert alert-dismissible alert-danger">' + exception + "</div>";
    set_error(exception);
    bs_loading.hide();
    return;
  }

  if (ajax_step.url !== undefined && ajax_step.step !== undefined) {
    try {
      let res = await fetch(ajax_step.url);
      if (!res.ok) throw new Error(ajax_step.url + ": " + res.statusText);
      let html = await res.text();
      html_leftcol.innerHTML = html;
    } catch (e) {
      set_error(e);
    }
    try {
      set_current_step(ajax_step);
    } catch (exception) {
      theseus_log(exception);
      html_leftcol.innerHTML = '<div class="alert alert-dismissible alert-danger">Error 4, see console.</div>';
      set_error("Error 4, see console.");
      return;
    }
  } else if (ajax_step.error !== undefined) {
    html_leftcol.innerHTML = '<div class="alert alert-dismissible alert-danger">' +  ajax_step.error + "</div>";
    set_error(ajax_step.error)
  } else {
    html_leftcol.innerHTML = '<div class="alert alert-dismissible alert-danger">Unknown response: ' +  JSON.stringify(ajax_step) + "</div>";
    set_error('Unknown response: ' +  JSON.stringify(ajax_step))
  }
  bs_loading.hide();
}



async function update_mesh_status(mesh_hash) {
  theseus_log(mesh_hash);
  let response;
  try {
    let res = await fetch("meshing_status.php?id="+id+"&mesh_hash="+mesh_hash);
    if (!res.ok) throw new Error("Network error");
    response = await res.json();
  } catch (exception) {
    set_error("Error 5, see console.");
    theseus_log(exception);
    return false;
  }
  set_warning((response["warning"] === undefined) ? "" : response["warning"]);
  set_error((response["error"] === undefined) ? "" : response["error"]);
  if (response["status"] == "running") {
    mesh_status_edges.innerHTML = response["edges"];
    mesh_status_faces.innerHTML = response["faces"];
    mesh_status_volumes.innerHTML = response["volumes"];
    if (response["done_edges"]) {
      progress_edges.classList.remove("bg-info");
      progress_edges.classList.add("bg-success");
      progress_edges.style.width = "100%";
    } else {
      progress_edges.style.width = response["progress_edges"] + "%";
    }
    if (response["done_faces"]) {
      progress_faces.classList.remove("bg-info");
      progress_faces.classList.add("bg-success");
      progress_faces.style.width = "100%";
    } else {
      progress_faces.style.width = response["progress_faces"] + "%";
    }
    if (response["done_volumes"]) {
      progress_volumes.classList.remove("bg-info");
      progress_volumes.classList.add("bg-success");
      progress_volumes.style.width = "100%";
    } else {
      progress_volumes.style.width = response["progress_volumes"] + "%";
    }
    if (response["done_data"]) {
      progress_data.classList.remove("bg-info");
      progress_data.classList.add("bg-success");
      progress_data.style.width = "100%";
    } else {
      progress_data.style.width = response["progress_data"] + "%";
    }
    mesh_log.innerHTML = response["log"];
    setTimeout(() => update_mesh_status(mesh_hash), 1000);
  } else {
    setTimeout(() => change_step(1), 1000);
  }
  return true;
}

async function cancel_meshing(mesh_hash) {
  theseus_log("cancel_meshing("+mesh_hash+")");
  let response;
  try {
    let res = await fetch("meshing_cancel.php?id="+id+"&mesh_hash="+mesh_hash);
    if (!res.ok) throw new Error("Network error");
    response = await res.json();
  } catch (exception) {
    set_error("Error 6, see console.");
    theseus_log(exception);
    return false;
  }
  return change_step(1);
}

async function relaunch_meshing(mesh_hash) {
  theseus_log("relaunch_meshing("+mesh_hash+")");
  let response;
  try {
    let res = await fetch("meshing_relaunch.php?id="+id+"&mesh_hash="+mesh_hash);
    if (!res.ok) throw new Error("Network error");
    response = await res.json();
  } catch (exception) {
    set_error("Error 7, see console.");
    theseus_log(exception);
    return false;
  }
  return change_step(1);
}

async function cancel_solving(problem_hash) {
  theseus_log("cancel_solving("+problem_hash+")");
  let response;
  try {
    let res = await fetch("solving_cancel.php?id="+id+"&problem_hash="+problem_hash);
    if (!res.ok) throw new Error("Network error");
    response = await res.json();
  } catch (exception) {
    set_error("Error 6, see console.");
    theseus_log(exception);
    return false;
  }
  return change_step(1);
}


async function relaunch_solving(problem_hash) {
  theseus_log("relaunch_solving("+problem_hash+")");
  let response;
  try {
    let res = await fetch("solving_relaunch.php?id="+id+"&problem_hash="+problem_hash);
    if (!res.ok) throw new Error("Network error");
    response = await res.json();
  } catch (exception) {
    set_error("Error 8, see console.");
    theseus_log(exception);
    return false;
  }
  return change_step(3);
}

async function update_problem_status(problem_hash) {
  theseus_log(problem_hash);
  let response;
  try {
    let res = await fetch("solving_status.php?id="+id+"&problem_hash="+problem_hash);
    if (!res.ok) throw new Error("Network error");
    response = await res.json();
  } catch (exception) {
    set_error("Error 9, see console.");
    theseus_log(exception);
    return false;
  }
  set_warning((response["warning"] === undefined) ? "" : response["warning"]);
  set_error((response["error"] === undefined) ? "" : response["error"]);
  if (response["status"] == "running") {
    if (response["done_mesh"]) {
      progress_mesh.classList.remove("bg-info");
      progress_mesh.classList.add("bg-success");
      progress_mesh.style.width = "100%";
    } else {
      progress_mesh.style.width = response["mesh"] + "%";
    }

    if (response["done_build"]) {
      progress_build.classList.remove("bg-info");
      progress_build.classList.add("bg-success");
      progress_build.style.width = "100%";
    } else {
      progress_build.style.width = response["build"] + "%";
    }

    if (response["done_solve"]) {
      progress_solve.classList.remove("bg-info");
      progress_solve.classList.add("bg-success");
      progress_solve.style.width = "100%";
    } else {
      progress_solve.style.width = response["solve"] + "%";
    }
    if (response["done_post"]) {
      progress_post.classList.remove("bg-info");
      progress_post.classList.add("bg-success");
      progress_post.style.width = "100%";
    } else {
      progress_post.style.width = response["post"] + "%";
    }
    setTimeout(() => update_problem_status(problem_hash), 1000);
  } else {
    setTimeout(() => change_step(3), 1000);
  }
  return true;
}


async function geo_show() {
  let response;
  try {
    let res = await fetch("mesh_inp_show.php?id=" + id);
    if (!res.ok) throw new Error("Network error");
    response = await res.json();
    div_geo_html.innerHTML = response["html"];
    plain_geo = response["plain"];
    bs_modal_geo.show();
    return true;
  } catch (exception) {
    set_error("Error 11, see console.");
    theseus_log(exception);
    return false;
  }
}
async function geo_log(mesh_hash) {
  let response;
  try {
    let res = await fetch("mesh_log.php?id="+id+"&mesh_hash="+mesh_hash);
    if (!res.ok) throw new Error("Network error");
    response = await res.json();
    if (response["stderr"] == "") {
      bootstrap_hide("div_err_html");
    } else {
      bootstrap_block("div_err_html");
    }
    div_err_html.innerHTML = response["stderr"];
    div_log_html.innerHTML = response["stdout"];
    bs_modal_log.show();
    return true;
  } catch (exception) {
    set_error("Error 12, see console.");
    theseus_log(exception);
    return false;
  }
}



async function geo_save() {
  try {
    let res = await fetch("mesh_inp_save.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + id + "&geo=" + encodeURIComponent(text_geo_edit.value)
    });
    if (!res.ok) throw new Error("Network error");
    let response = await res.json();
    if (response["status"] == "ok") {
      geo_cancel();
      bs_modal_geo.hide();
      change_step(1);
    } else {
      document.getElementById("geo_error_message").innerHTML = response["error"];
      bootstrap_block("geo_error_message");
    }
    return true;
  } catch (exception) {
    set_error("Error 13, see console.");
    theseus_log(exception);
    return false;
  }
}



async function fee_show() {
  let response;
  try {
    let res = await fetch("problem_fee.php?id=" + id);
    if (!res.ok) throw new Error("Network error");
    response = await res.json();
    div_fee_html.innerHTML = response["html"];
    text_fee_edit_header.innerHTML = response["header"];
    plain_fee = response["plain"];
    bs_modal_fee.show();
    return true;
  } catch (exception) {
    set_error("Error 14, see console.");
    theseus_log(exception);
    return false;
  }
}


async function fee_save() {
  try {
    let res = await fetch("problem_fee_save.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + id + "&fee=" + encodeURIComponent(text_fee_edit.value)
    });
    if (!res.ok) throw new Error("Network error");
    let response = await res.json();
    if (response["status"] == "ok") {
      fee_cancel();
      bs_modal_fee.hide();
      change_step(2);
    } else {
      fee_error_message.innerHTML = response["error"];
      bootstrap_block("fee_error_message");
    }
    return true;
  } catch (exception) {
    set_error("Error 15, see console.");
    theseus_log(exception);
    return false;
  }
}

