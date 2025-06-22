// TODO: understand when to update
function update_mesh(mesh_hash = "") {
  var request_mesh = new XMLHttpRequest();
  if (mesh_hash != "") {
    request_mesh.open("GET", "mesh_data.php?id="+id+"&mesh="+mesh_hash, false);  // false makes the request synchronous
  } else {
    request_mesh.open("GET", "mesh_data.php?id="+id, false);  // false makes the request synchronous
  }
  request_mesh.send(null);

  if (request_mesh.status === 200) {
    try {
      data = JSON.parse(request_mesh.responseText);
    } catch (exception) {
      set_error(request_results.responseText);
      theseus_log(request_mesh.responseText);
      theseus_log(exception);
      return false;
    }

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
    if (mesh_hash != "") {
      current_mesh = mesh_hash;
    }
  }
  
  return true;
}

// TODO: return true or false
function update_results(problem_hash = "") {

  // TODO: check if we need to pull the data or we can use what we already have
  var request_results = new XMLHttpRequest();
  request_results.open("GET", "results_data.php?id="+id, false);  // false makes the request synchronous
  request_results.send(null);

  if (request_results.status === 200) {
    try {
      data = JSON.parse(request_results.responseText);
    } catch (exception) {
      set_error(request_results.responseText);
      theseus_log(request_results.responseText);
      theseus_log(exception);
      return;
    }

    if (data["error"] === undefined || data["error"] == "") {

      let coord_indexes = surfaces_edges_set.getAttribute("coordIndex");
      let nodes = document.getElementById("nodes").getAttribute("point");
      
      if (data["nodes_warped"] !== undefined) {
        results_surfaces_edges.innerHTML = '\
<Appearance><Material emissiveColor="0 0 0" diffuseColor="0 0 0"></Material></Appearance>\
<IndexedLineSet coordIndex="' + coord_indexes + '"><Coordinate id="nodes_warped"></Coordinate></IndexedLineSet>\
<ScalarInterpolator id="si" key="0 1" keyValue="0 1"><ScalarInterpolator>\
<CoordinateInterpolator id="ci" key="0 1" keyValue="' + nodes + ' ' + data["nodes_warped"] + '"></CoordinateInterpolator>\
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
<Coordinate use="'+coords_use+'"></Coordinate>\
<Color id="color_scalar" color="'+ color_string +'"></Color>\
</IndexedFaceSet>';
    
      if (problem_hash != "") {
        current_results = problem_hash;
      }
    } else {
      set_error(data["error"]);
    }
  }
}

function ajax2yaml(field, value) {
  theseus_log("ajax2yaml("+field+","+value+")");

  var request_yaml = new XMLHttpRequest();
  // TODO: post? json?
  request_yaml.open("GET", "ajax2yaml.php?id="+id+"&field="+encodeURIComponent(field)+"&value="+encodeURIComponent(value), false);
  request_yaml.send(null);

  if (request_yaml.status === 200) {
    try {
      response = JSON.parse(request_yaml.responseText);
    } catch (exception) {
      set_error(request_yaml.responseText);
      theseus_log(request_yaml.responseText);
      theseus_log(exception);
      return;
    }

    // warnings & errors
    set_warning((response["warning"] === undefined) ? "" : response["warning"]);
    set_error((response["error"] === undefined) ? "" : response["error"]);

    // fill content html
    if (response["content_id"] !== undefined && response["content_html"] !== undefined) {
      for (let i = 0; i < response["content_id"].length; i++) {
        document.getElementById(response["content_id"][i]).innerHTML = response["content_html"][i];
      }
    }

    // show & hide stuff
    if (response["hide"] !== undefined) {
      for (let i = 0; i < response["hide"].length; i++) {
        bootstrap_hide(response["hide"][i]);
      }
    }
    if (response["block"] !== undefined) {
      for (let i = 0; i < response["block"].length; i++) {
        bootstrap_block(response["block"][i]);
      }
    }
    if (response["inline"] !== undefined) {
      for (let i = 0; i < response["inline"].length; i++) {
        bootstrap_inline(response["inline"][i]);
      }
    }


  } else {
    set_error("Internal error, see console.");
  }
  theseus_log(response);
}


// TODO: unify
function ajax2problem(field, value) {
  theseus_log("ajax2problem("+field+","+value+")");

  var request_yaml = new XMLHttpRequest();
  // TODO: post? json?
  request_yaml.open("GET", "ajax2problem.php?id="+id+"&field="+encodeURIComponent(field)+"&value="+encodeURIComponent(value), false);
  request_yaml.send(null);

  if (request_yaml.status === 200) {
    try {
      response = JSON.parse(request_yaml.responseText);
    } catch (exception) {
      set_error(request_yaml.responseText);
      theseus_log(request_yaml.responseText);
      theseus_log(exception);
      return;
    }

    // warnings & errors
    set_warning((response["warning"] === undefined) ? "" : response["warning"]);
    set_error((response["error"] === undefined) ? "" : response["error"]);

    // fill content html
    if (response["content_id"] !== undefined && response["content_html"] !== undefined) {
      for (let i = 0; i < response["content_id"].length; i++) {
        document.getElementById(response["content_id"][i]).innerHTML = response["content_html"][i];
      }
    }

    // show & hide stuff
    if (response["hide"] !== undefined) {
      for (let i = 0; i < response["hide"].length; i++) {
        bootstrap_hide(response["hide"][i]);
      }
    }
    if (response["block"] !== undefined) {
      for (let i = 0; i < response["block"].length; i++) {
        bootstrap_block(response["block"][i]);
      }
    }
    if (response["inline"] !== undefined) {
      for (let i = 0; i < response["inline"].length; i++) {
        bootstrap_inline(response["inline"][i]);
      }
    }


  } else {
    set_error("Internal error, see console.");
  }
  theseus_log(response);
}



// TODO: unify, this is the same as above with different url
function ajax2mesh(field, value) {
  theseus_log("ajax2mesh("+field+","+value+")");

  var request_yaml = new XMLHttpRequest();
  // TODO: post? json?
  request_yaml.open("GET", "ajax2mesh.php?id="+id+"&field="+encodeURIComponent(field)+"&value="+encodeURIComponent(value), false);
  request_yaml.send(null);

  if (request_yaml.status === 200) {
    try {
      response = JSON.parse(request_yaml.responseText);
    } catch (exception) {
      set_error(request_yaml.responseText);
      theseus_log(request_yaml.responseText);
      theseus_log(exception);
      return;
    }

    // warnings & errors
    set_warning((response["warning"] === undefined) ? "" : response["warning"]);
    set_error((response["error"] === undefined) ? "" : response["error"]);

    // fill content html
    if (response["content_id"] !== undefined && response["content_html"] !== undefined) {
      for (let i = 0; i < response["content_id"].length; i++) {
        document.getElementById(response["content_id"][i]).innerHTML = response["content_html"][i];
      }
    }

    // show & hide stuff
    if (response["hide"] !== undefined) {
      for (let i = 0; i < response["hide"].length; i++) {
        bootstrap_hide(response["hide"][i]);
      }
    }
    if (response["block"] !== undefined) {
      for (let i = 0; i < response["block"].length; i++) {
        bootstrap_block(response["block"][i]);
      }
    }
    if (response["inline"] !== undefined) {
      for (let i = 0; i < response["inline"].length; i++) {
        bootstrap_inline(response["inline"][i]);
      }
    }


  } else {
    set_error("Internal error, see console.");
  }
  theseus_log(response);
}



function ajax_change_step() {
  
//  theseus_log("ajax_change_step, next = "+next_step+" current = "+current_step);
  html_leftcol.removeEventListener("hidden.bs.collapse", wrapper_leftcol_collape);  
  
  let ajax_step = new XMLHttpRequest()

  // ajax_step.open("GET", "change_step.php?id="+id + "&next_step="+next_step + "&current_step="+current_step, false);
  // ajax_step.send(null);

  ajax_step.open("POST", "change_step.php", false);
  ajax_step.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

  // let post_string = "";
  // let form = document.getElementById("form_left");
  // if (form != null) {
  //   let data = new FormData(form);
  //   post_string = "&" + new URLSearchParams(data).toString();
  // }
  // ajax_step.send("id="+id + "&next_step="+next_step + "&current_step="+current_step + post_string);
  ajax_step.send("id="+id + "&next_step="+next_step + "&current_step="+current_step);

  theseus_log("id="+id + "&next_step="+next_step + "&current_step="+current_step);

  if (ajax_step.status === 200) {
    let response;
    try {
      theseus_log(ajax_step.responseText);
      response = JSON.parse(ajax_step.responseText);
    } catch (exception) {
      theseus_log(exception);
      html_leftcol.innerHTML = '<div class="alert alert-dismissible alert-danger">' +  ajax_step.responseText + "</div>";
      set_error(ajax_step.responseText);
      bs_loading.hide();
      return;
    }

    if (response.url !== undefined && response.step !== undefined) {
      
      let ajax_left = new XMLHttpRequest();
      ajax_left.open("GET", response.url, false);
      ajax_left.send(null);
        
      if (ajax_left.status === 200) { 
        html_leftcol.innerHTML = ajax_left.responseText;
      } else {
        // html_leftcol.innerHTML = '<div class="alert alert-dismissible alert-danger">' +  ajax_left.status + " " + ajax_left.statusText + "</div>";
        set_error(ajax_left.status + " " + ajax_left.statusText)
      }
      try {
        // all good!
        set_current_step(response);
      } catch (exception) {
        theseus_log(exception);
        html_leftcol.innerHTML = '<div class="alert alert-dismissible alert-danger">Internal error, see console.</div>';
        set_error("Internal error, see console.");
        return;
      }
      
    } else if (response.error !== undefined) {
      html_leftcol.innerHTML = '<div class="alert alert-dismissible alert-danger">' +  response.error + "</div>";
      set_error(response.error)
    } else {
      html_leftcol.innerHTML = '<div class="alert alert-dismissible alert-danger">Unknown response: ' +  ajax_step.responseText + "</div>";
      set_error('Unknown response: ' +  ajax_step.responseText)
    }
  }
  bs_loading.hide();
}



function update_mesh_status(mesh_hash) {
  // console.log("updated mesh status " + (++counter ))
  theseus_log(mesh_hash);
  
  var request_mesh_status = new XMLHttpRequest();
  request_mesh_status.open("GET", "meshing_status.php?id="+id+"&mesh_hash="+mesh_hash, false);
  request_mesh_status.send(null);

  if (request_mesh_status.status === 200) {
    theseus_log(request_mesh_status.responseText);
    try {
      response = JSON.parse(request_mesh_status.responseText);
    } catch (exception) {
      theseus_log(request_mesh_status.responseText);
      theseus_log(exception);
      set_error(request_mesh_status.responseText);
      return false;
    }

    // warnings & errors
    set_warning((response["warning"] === undefined) ? "" : response["warning"]);
    set_error((response["error"] === undefined) ? "" : response["error"]);
    
    if (response["status"] == "running") {
    
      // console.log("mesh status running")
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
    
      setTimeout(function() {
        return update_mesh_status(mesh_hash);
      }, 1000);
    } else {
      // console.log("mesh status done")
      
      setTimeout(function() {
        return change_step(1);
      }, 1000);
      
      // change_step(1);
    }

  } else {
    set_error("Internal error, see console.");
    return false;
  }  
  
  return true;
}


function cancel_meshing(mesh_hash) {
  theseus_log("cancel_meshing("+mesh_hash+")");

  var request_cancel = new XMLHttpRequest();
  request_cancel.open("GET", "meshing_cancel.php?id="+id+"&mesh_hash="+mesh_hash, false);
  request_cancel.send(null);

  if (request_cancel.status === 200) {
    try {
      response = JSON.parse(request_cancel.responseText);
    } catch (exception) {
      theseus_log(request_cancel.responseText);
      theseus_log(exception);
      set_error(request_cancel.responseText);
      return false;
    }
  }
  
  return change_step(1);
}

function relaunch_meshing(mesh_hash) {
  theseus_log("relaunch_meshing("+mesh_hash+")");

  var request_relaunch = new XMLHttpRequest();
  request_relaunch.open("GET", "meshing_relaunch.php?id="+id+"&mesh_hash="+mesh_hash, false);
  request_relaunch.send(null);

  if (request_relaunch.status === 200) {
    try {
      response = JSON.parse(request_relaunch.responseText);
    } catch (exception) {
      theseus_log(request_relaunch.responseText);
      theseus_log(exception);
      set_error(request_relaunch.responseText);
      return false;
    }
  }
  
  return change_step(1);
}

function relaunch_solving(problem_hash) {
  theseus_log("relaunch_solving("+problem_hash+")");

  var request_relaunch = new XMLHttpRequest();
  request_relaunch.open("GET", "solving_relaunch.php?id="+id+"&problem_hash="+problem_hash, false);
  request_relaunch.send(null);

  if (request_relaunch.status === 200) {
    try {
      response = JSON.parse(request_relaunch.responseText);
    } catch (exception) {
      theseus_log(request_relaunch.responseText);
      theseus_log(exception);
      set_error(request_relaunch.responseText);
      return false;
    }
  }
  
  return change_step(3);
}


function update_problem_status(problem_hash) {
  // console.log("update problem status " + (++counter ))
  theseus_log(problem_hash);
  
  var request_problem_status = new XMLHttpRequest();
  request_problem_status.open("GET", "solving_status.php?id="+id+"&problem_hash="+problem_hash, false);
  request_problem_status.send(null);

  if (request_problem_status.status === 200) {
    theseus_log(request_problem_status.responseText);
    try {
      response = JSON.parse(request_problem_status.responseText);
    } catch (exception) {
      theseus_log(request_problem_status.responseText);
      theseus_log(exception);
      set_error(request_problem_status.responseText);
      return false;
    }

    // warnings & errors
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
/*
      if (response["done_data"]) {
        progress_data.classList.remove("bg-info");
        progress_data.classList.add("bg-success");
        progress_data.style.width = "100%";
      } else {
        progress_data.style.width = response["data"] + "%";
      }
*/
      setTimeout(function() {
        return update_problem_status(problem_hash);
      }, 1000);
    } else {
      setTimeout(function() {
        return change_step(3);
      }, 1000);
    }

  } else {
    set_error("Internal error, see console.");
    return false;
  }  
  return true;
}


function geo_show() {
  var request_geo = new XMLHttpRequest();
  request_geo.open("GET", "mesh_inp_show.php?id="+id, false);
  request_geo.send(null);

  if (request_geo.status === 200) {
    theseus_log(request_geo.responseText);
    try {
      response = JSON.parse(request_geo.responseText);
      div_geo_html.innerHTML = response["html"];
      plain_geo = response["plain"];
      
      bs_modal_geo.show();
    } catch (exception) {
      theseus_log(request_geo.responseText);
      theseus_log(exception);
      set_error(request_geo.responseText);
      return false;
    }
  }
  return true;
}

function geo_log(mesh_hash) {
  var request_log = new XMLHttpRequest();
  request_log.open("GET", "mesh_log.php?id="+id+"&mesh_hash="+mesh_hash, false);
  request_log.send(null);

  if (request_log.status === 200) {
    theseus_log(request_log.responseText);
    try {
      response = JSON.parse(request_log.responseText);
      if (response["stderr"] == "") {
        bootstrap_hide("div_err_html");
      } else {
        bootstrap_block("div_err_html");
      }
      div_err_html.innerHTML = response["stderr"];
      div_log_html.innerHTML = response["stdout"];
      bs_modal_log.show();
    } catch (exception) {
      theseus_log(request_log.responseText);
      theseus_log(exception);
      set_error(request_log.responseText);
      return false;
    }
  }
  return true;
}


function geo_save() {

  var request_geo = new XMLHttpRequest();
  request_geo.open("POST", "mesh_inp_save.php", false);
  request_geo.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  request_geo.send("id="+id + "&geo="+encodeURIComponent(text_geo_edit.value));

  if (request_geo.status === 200) {
    try {
      response = JSON.parse(request_geo.responseText);
      if (response["status"] == "ok") {
        geo_cancel();
        bs_modal_geo.hide();
        change_step(1);
      } else{
        document.getElementById("geo_error_message").innerHTML = response["error"];
        bootstrap_block("geo_error_message");
      }
    } catch (exception) {
      theseus_log(request_geo.responseText);
      theseus_log(exception);
      set_error(request_geo.responseText);
      return false;
    }
  }
  return true;
}



function fee_show() {
  var request_fee = new XMLHttpRequest();
  request_fee.open("GET", "problem_fee.php?id="+id, false);
  request_fee.send(null);

  if (request_fee.status === 200) {
    theseus_log(request_fee.responseText);
    try {
      response = JSON.parse(request_fee.responseText);
//      text_fee_edit_header.innerHTML = response["header"];
      div_fee_html.innerHTML = response["html"];
      plain_fee = response["plain"];
      
      bs_modal_fee.show();
    } catch (exception) {
      theseus_log(request_fee.responseText);
      theseus_log(exception);
      set_error(request_fee.responseText);
      return false;
    }
  }
  return true;
}


function fee_save() {

  var request_fee = new XMLHttpRequest();
  request_fee.open("POST", "problem_fee_save.php", false);
  request_fee.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  request_fee.send("id="+id + "&fee="+encodeURIComponent(text_fee_edit.value));

  if (request_fee.status === 200) {
    try {
      response = JSON.parse(request_fee.responseText);
      if (response["status"] == "ok") {
        fee_cancel();
        bs_modal_fee.hide();
        change_step(2);
      } else{
        fee_error_message.innerHTML = response["error"];
        bootstrap_block("fee_error_message");
      }
    } catch (exception) {
      theseus_log(request_fee.responseText);
      theseus_log(exception);
      set_error(request_fee.responseText);
      return false;
    }
  }
  return true;
}

