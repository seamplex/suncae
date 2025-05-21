var debug = true;
var counter = 0;

var toolbar_shown = {};
var show_bs_collapse_event_added = false;

toolbar_shown["left_xs"] = false;
toolbar_shown["left_sm"] = false;
toolbar_shown["left_md"] = false;
toolbar_shown["left_lg"] = true;
toolbar_shown["left_xl"] = true;
toolbar_shown["left_xxl"] = true;

toolbar_shown["right_xs"] = false;
toolbar_shown["right_sm"] = false;
toolbar_shown["right_md"] = false;
toolbar_shown["right_lg"] = false;
toolbar_shown["right_xl"] = false;
toolbar_shown["right_xxl"] = true;

var bootstrap_sizes = ["xs", "sm", "md", "lg", "xl", "xxl"];

var color = {};
color["base"] =    [0.65, 0.65, 0.65];
color["general"] = [0.80, 0.80, 0.80];
// color["solids"] =  [1.00, 1.00, 1.00];
// color["hide"] =    [1.00, 0.25, 0.50];
// color["measure"] = [0.38, 0.13, 0.76];
color["error"] =   [1.00, 0.00, 0.00];

color["refinement_1"] = [1.00, 0.40, 0.00];
color["refinement_2"] = [0.40, 0.00, 1.00];
color["refinement_3"] = [1.00, 0.00, 0.40];
color["refinement_4"] = [1.00, 0.90, 0.50];
color["refinement_5"] = [0.16, 0.83, 1.00];
color["refinement_6"] = [0.40, 1.00, 0.40];
color["refinement_7"] = [0.67, 0.57, 0.57];
color["refinement_8"] = [1.00, 0.33, 0.33];
color["refinement_9"] = [0.16, 0.83, 0.00];
color["refinement_10"] = [0.80, 0.90, 0.85];

var geo_entity = ["point", "edge", "face"];

var id = "";
var current_step = 0;
var next_step = 0;
var current_clip_plane = "";
var already_changed_edge_width = false;
var already_changed_vertex_size = false;
var current_mesh = "";
var current_results = "";
var current_bc = 0;
var current_dim = 2;
var n_bcs = 0;
var n_mesh_field = 0;

var results_indexedfaceset_set = "";
var target_warp_fraction = 0;
var warp_max = 1;

// globals, same width
var html_loadsing;
var html_leftcol;
var bs_loading;
var bs_leftcol;

var bs_modal_geo;
var bs_modal_log;
var bs_modal_fee;
var plain_geo = "";

var x3d_geometry;
var x3d_mesh_surfaces_edges;
var x3d_mesh_surfaces_faces;
var x3d_results_surfaces_edges;
var x3d_results_surfaces_faces;

var x3d_small_axes;


function theseus_log(s) {
  if (debug) {
    console.log(s)
  }
}

function fit_all_view() {
  canvas.runtime.fitAll();
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
function bootstrap_inline(id) {
  document.getElementById(id).classList.add("d-inline");
  document.getElementById(id).classList.remove("d-none");
}
function bootstrap_flex(id) {
  document.getElementById(id).classList.add("d-flex");
  document.getElementById(id).classList.remove("d-none");
}

// TODO: hacen falta todos los getElementById o los podemos hacer una vez en el DOMContentLoaded y ya?


// -------------------------------------------
function toggle_toolbar(bar, size) {

  // the xs is handled below
  for (let i = 1; i < 6; i++) {
    document.getElementById(bar).classList.remove("d-"+bootstrap_sizes[i]+"-block");
    document.getElementById(bar).classList.remove("d-"+bootstrap_sizes[i]+"-none");
  } 
  
  if (toolbar_shown[bar+"_"+size] == true) {
    bootstrap_hide(bar);
    for (let i = 0; i < 6; i++) {
      toolbar_shown[bar+"_"+bootstrap_sizes[i]] = false;
    }
  } else {
    bootstrap_block(bar);
    for (let i = 0; i < 6; i++) {
      toolbar_shown[bar+"_"+bootstrap_sizes[i]] = true;
    }
  }
}

// -------------------------------------------
function init_small_axes() {
  // TODO: choose to use or not
  main_viewpoint.addEventListener("viewpointChanged", update_named_cube, false);
  small_axes.setAttribute("render", "true");
}

// -------------------------------------------
function update_named_cube(evt) {
  if (evt) {
    var rot = evt.orientation;
    var invrot = x3dom.fields.Quaternion.axisAngle(rot[0], rot[1]).inverse().toAxisAngle();
    small_axes.setAttribute("rotation", invrot[0].x + " " + invrot[0].y + " " + invrot[0].z + " " + invrot[1]);
  }
}

// -------------------------------------------
function set_clip_plane(plane, offset = 0) {
  current_clip_plane = plane;

  // turn off all the planes
  clip_plane_x.setAttribute("enabled", "false");
  clip_plane_x.setAttribute("on", "false");
  clip_plane_y.setAttribute("enabled", "false");
  clip_plane_y.setAttribute("on", "false");
  clip_plane_z.setAttribute("enabled", "false");
  clip_plane_z.setAttribute("on", "false");
  
  // show faces/triangles/results
  if (current_step == 1) {
    cad_faces(1);
  }
  
  if (current_clip_plane == "") {
    
    for (let i = 1; i <= n_faces; i++) {
      document.getElementById("cad__setface"+i).setAttribute("solid", "true");
      document.getElementById("cad__matface"+i).setAttribute("transparency", 0);
    }
    
    clip_offset_range.disabled = true;
    clip_revert_checkbox.disabled = true;

  } else {
    
    for (let i = 1; i <= n_faces; i++) {
      document.getElementById("cad__setface"+i).setAttribute("solid", "false");
      document.getElementById("cad__matface"+i).setAttribute("transparency", 0);
    }
    
    document.getElementById("clip_plane_" + plane).setAttribute("enabled", "true");
    document.getElementById("clip_plane_" + plane).setAttribute("on", "true");
    
    clip_offset_range.disabled = false;
    clip_revert_checkbox.disabled = false;
    
    set_clip_offset(offset);
  }
}

// -------------------------------------------
function set_clip_offset(offset) {

  document.getElementById("clip_offset_range").value = offset;
  dir = (clip_revert_checkbox.checked) ? +1 : -1;

  if (current_clip_plane == "x") {
    clip_plane_x.setAttribute("plane", dir + " 0 0 " + (-dir*offset));
    clip_offset_range.setAttribute("min", Math.floor(xmin));
    clip_offset_range.setAttribute("max", Math.ceil(xmax));
  } else if (current_clip_plane == "y") {
    clip_plane_y.setAttribute("plane", "0 " + dir + " 0 " + (-dir*offset));
    clip_offset_range.setAttribute("min", Math.floor(ymin));
    clip_offset_range.setAttribute("max", Math.ceil(ymax));
  } else if (current_clip_plane == "z") {
    clip_plane_z.setAttribute("plane", "0 0 " + dir + " " + (-dir*offset));
    clip_offset_range.setAttribute("min", Math.floor(zmin));
    clip_offset_range.setAttribute("max", Math.ceil(zmax));
  }
}


// ---------------------------
function big_axes(factor) {
  
  if (factor > 0.05) {
    axes.setAttribute("render", "true");
    axis_line_coord_x.setAttribute("point",          factor*(xmin-characteristic_length) + " 0 0, "   + factor*(xmax+characteristic_length) + " 0 0");
    axis_line_coord_y.setAttribute("point", "0 "   + factor*(ymin-characteristic_length) + " 0, 0 "   + factor*(ymax+characteristic_length) + " 0");
    axis_line_coord_z.setAttribute("point", "0 0 " + factor*(zmin-characteristic_length) + ", 0 0 " + factor*(zmax+characteristic_length));
    
    axis_arrow_x.setAttribute("translation",          factor*(xmax+characteristic_length) + " 0 0");
    axis_arrow_y.setAttribute("translation", "0 "   + factor*(ymax+characteristic_length) + " 0");
    axis_arrow_z.setAttribute("translation", "0 0 " + factor*(zmax+characteristic_length));

    axis_cone_x.setAttribute("bottomRadius", factor*0.1*characteristic_length);
    axis_cone_y.setAttribute("bottomRadius", factor*0.1*characteristic_length);
    axis_cone_z.setAttribute("bottomRadius", factor*0.1*characteristic_length);

    axis_cone_x.setAttribute("height",       factor*0.5*characteristic_length);
    axis_cone_y.setAttribute("height",       factor*0.5*characteristic_length);
    axis_cone_z.setAttribute("height",       factor*0.5*characteristic_length);
    
  } else {
    document.getElementById("axes").setAttribute("render", "false");
  }

}
  
// -------------------------
function cad_faces(opacity) {
  let eps = 0.025;
  let trans = 1-opacity;
//  var solid;
  
  document.getElementById("range_cad_faces").value = opacity;

  if (opacity < eps)  {
    document.getElementById("cad__faces").setAttribute("render", "false");
    trans = 1;
//     solid = "true";
  } else if (trans < eps) {
    document.getElementById("cad__faces").setAttribute("render", "true");
    trans = 0;
//     solid = "true";
  } else {
    document.getElementById("cad__faces").setAttribute("render", "true");
    mesh_triangles("hide");
//     solid = "false";
  }

  for (i = 1; i <= n_faces; i++) {
//     document.getElementById("cad__setface"+i).setAttribute("solid", "false");
    document.getElementById("cad__matface"+i).setAttribute("transparency", trans);
  }
}


// -------------------------
function cad_edges(width) {
  already_changed_edge_width = true
  document.getElementById("range_cad_edges").value = width;

  if (width < 1) {
    document.getElementById("cad__edges").setAttribute("render", "false");
  } else {
    document.getElementById("cad__edges").setAttribute("render", "true");
    for (var i = 1; i <= n_edges; i++) {
      edge = document.getElementById("cad__propedge"+i);
      if (edge) {
        edge.setAttribute("linewidthScaleFactor", width-1);
      }
    }
  }
}

// -------------------------
function cad_vertices(rel_size) {
  var eps = 0.025;
  already_changed_vertex_size = true;
  document.getElementById("range_cad_vertices").value = rel_size;

  if (rel_size < eps) {
    document.getElementById("cad__vertices").setAttribute("render", "false");
  } else {
    document.getElementById("cad__vertices").setAttribute("render", "true");
    abs_size = rel_size * 0.1 * characteristic_length;
    for (i = 1; i <= n_vertices; i++) {
      document.getElementById("cad__vertex"+i).setAttribute("scale", abs_size + " " + abs_size + " " + abs_size + " ");
    }
  }
}



document.addEventListener("DOMContentLoaded", () => {

//   x3dom.runtime.ready = hook_post();
  
  // this comes after the DOM is ready otherwise these are not defined
  html_loading = document.getElementById("collapse_loading");
  html_leftcol = document.getElementById("collapse_leftcol");

  bs_loading   = new bootstrap.Collapse(html_loading, { toggle: false });
  bs_leftcol   = new bootstrap.Collapse(html_leftcol, { toggle: false });
  
  x3d_geometry = document.getElementById("geometry");
  x3d_bounding_box = document.getElementById("bbox");
  x3d_mesh_surfaces_edges = document.getElementById("surfaces_edges");
  x3d_mesh_surfaces_faces = document.getElementById("surfaces_faces");
  x3d_results_surfaces_edges = document.getElementById("results_surfaces_edges");
  x3d_results_surfaces_faces = document.getElementById("results_surfaces_faces");
  
  x3d_small_axes = document.getElementById("small_axes");
  
  // when the "loading" html finishes collapsing, show the leftcol html
  html_loading.addEventListener("hidden.bs.collapse", (e) => { bs_leftcol.show() }, false);
  
  bs_modal_geo = new bootstrap.Modal(document.getElementById("modal_geo"));
  bs_modal_log = new bootstrap.Modal(document.getElementById("modal_log"));
  bs_modal_fee = new bootstrap.Modal(document.getElementById("modal_fee"));
  

});

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

function set_error(message) {
  error_message.innerHTML = message;
  if (message == "") {
    bootstrap_hide("error_message");
    if (document.getElementById("button_next") != undefined) {
      document.getElementById("button_next").disabled = false;
    }
  } else {
    bootstrap_block("error_message");
    if (document.getElementById("button_next") != undefined) {
      document.getElementById("button_next").disabled = true;
    }
  }
}

function set_warning(message) {
  document.getElementById("warning_message").innerHTML = message;
  if (message == "") {
    bootstrap_hide("warning_message");
  } else {
    bootstrap_block("warning_message");
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


function change_step(step) {

  next_step = step;
  // theseus_log("change_step("+next_step+")")

  // update nav, remove all classes
  for (let i = 1; i <= 3; i++) {
    let badge = document.getElementById("badge_step"+i);
    let span = document.getElementById("span_step"+i);
    let li = document.getElementById("li_step"+i); 

    // first remove all classes
    badge.classList.remove("bg-secondary");
    badge.classList.remove("bg-primary");
    badge.classList.add("bg-dark");
    span.classList.remove("text-secondary");
    span.classList.remove("text-primary");
    span.classList.add("text-dark");
    li.setAttribute("role", "");
    li.setAttribute("onclick", "");
  }

  if (html_loading.classList.contains("show")) {
    // if the spinner is already showing (which means this is the first load)
    // we just make the ajax call
    ajax_change_step();
  } else {
    // otherwise,
    // we need to make the ajax call only hiding the current left and showing the spinner
    html_leftcol.addEventListener("hidden.bs.collapse", wrapper_leftcol_collape, false);
    if (show_bs_collapse_event_added == false) {
      // theseus_log("mandioca")
      html_loading.addEventListener("shown.bs.collapse", (e) => { ajax_change_step() }, false);
      show_bs_collapse_event_added = true;
    }
    bs_leftcol.hide();
  }
  
}

// esto es asi: el addEventListener sobre hidden.bs.collapse se expande a los hijos por alguna razon
// aun cuando paso falso en el tercer argumento
// entonces lo que hago es despues de usar el evento, borrarlo
// para eso se necesita que la accion no sea anonima, pero el primer argumento de la accion es el evento
// y como collapse.show() se queja si se le pasa argumento, entonces hacemos un wrapper
function wrapper_leftcol_collape(e) {
//  theseus_log("wrapper_leftcol_collape("+e+")");
  bs_loading.show();
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


function set_current_step(response) {
  if (response.step === undefined || response.step < -4 || response.step > 4) {
    return false;
  }

  set_error("");
  current_bc = 0;
  current_step = response.step;
  target_warp_fraction = -1;
  
  if (current_step == 1) {
    if (current_mesh != response.mesh) {
      theseus_log("need to update mesh to " + response.mesh)
      if (update_mesh(response.mesh) == false) {
        return false;
      }
    }
    cad_faces(0);
    cad_edges(1);
    bounding_box("show");
    mesh_lines("show")
    mesh_triangles("show")
    results_lines("hide");
    results_faces("hide");
  } else if (current_step == 2) {
    cad_faces(1);
    cad_edges(1);
    bounding_box("hide");
    mesh_lines("hide")
    mesh_triangles("hide")
    results_lines("hide");
    results_faces("hide");
  } else if (current_step == 3) {
    theseus_log(response);
    update_results(response.results);
    cad_faces(0);
    cad_edges(1);
    cad_vertices(0);
    bounding_box("hide");
    mesh_lines("hide")
    mesh_triangles("hide");
    // TODO: improve
    if (problem == "mechanical") {
      results_lines("show");
    } else {
      results_lines("hide");
    }      
    results_faces("show");
  }
  
  for (let i = 1; i <= 3; i++) {
    let badge = document.getElementById("badge_step"+i);
    let span = document.getElementById("span_step"+i);
    let li = document.getElementById("li_step"+i); 

    // TODO: farthest
    // if (i < 3) {
      li.setAttribute("role", "button");
      li.setAttribute("onclick", "change_step("+i+")");
      if (i == Math.abs(current_step)) {
        badge.classList.add("bg-secondary");
        span.classList.add("text-secondary");
      } else  {
        badge.classList.add("bg-primary");
        span.classList.add("text-primary");
      }  
      badge.classList.remove("bg-dark");
      span.classList.remove("text-dark");
      
    // } else {
      // badge.classList.add("bg-dark");
      // span.classList.add("text-dark");
    // }
  }
  
  return true;
}

// -------------------------
function bounding_box(what) {
  let render = "";
  if (what == "toggle") {
    render = (check_bounding_box.checked) ? "true" : "false";
  } else if (what == "show") {
    render = "true";
    check_bounding_box.checked = true;
  } else if (what == "hide") {
    render = "false";
    check_bounding_box.checked = false;
  }
  
  x3d_bounding_box.setAttribute("render", render);
}

function mesh_triangles(what) {
  // TODO: no mezclar
  let render = "";
  if (what == "toggle") {
    render = (check_mesh_triangles.checked) ? "true" : "false";
  } else if (what == "show") {
    render = "true";
    check_mesh_triangles.checked = true;
  } else if (what == "hide") {
    render = "false";
    check_mesh_triangles.checked = false;
  }
  
  x3d_mesh_surfaces_faces.setAttribute("render", render);
  if (render == "true") {
    cad_faces(0);
  }
}

// -------------------------
function mesh_lines(what) {
  let render = "";
  if (what == "toggle") {
    render = (check_mesh_lines.checked) ? "true" : "false";
  } else if (what == "show") {
    render = "true";
    check_mesh_lines.checked = true;
  } else if (what == "hide") {
    render = "false";
    check_mesh_lines.checked = false;
  }
  
  x3d_mesh_surfaces_edges.setAttribute("render", render);
}

// -------------------------
function results_lines(what) {
  let render = "";
  if (what == "toggle") {
    render = (check_results_lines.checked) ? "true" : "false";
  } else if (what == "show") {
    render = "true";
    check_results_lines.checked = true;
  } else if (what == "hide") {
    render = "false";
    check_results_lines.checked = false;
  }
  
  x3d_results_surfaces_edges.setAttribute("render", render);
}

// -------------------------
function results_faces(what) {
  let render = "";
  if (what == "show") {
    render = "true";
  } else if (what == "hide") {
    render = "false";
  }
  
  x3d_results_surfaces_faces.setAttribute("render", render);
}


// -------------------------
function warp(val) {
 range_warp.value = val;
 text_warp.value = val;
 if (warp_max > 0) {
   si.setAttribute("set_fraction", (val/warp_max).toString());
 }
}

// -------------------------
function animate_warp() {
  
  if (target_warp_fraction >= 0) {
    let current_warp_fraction = range_warp.value/warp_max;
    let error = target_warp_fraction - current_warp_fraction;
    let n_steps = Math.abs(Math.floor(50*error));
    if (n_steps >= 1) {
      if (error > 0) {
        range_warp.stepUp(n_steps);
      } else {
        range_warp.stepDown(n_steps);
      }  
      warp(range_warp.value);
      setTimeout(animate_warp, 0);
    } else {
      warp(target_warp_fraction*warp_max);
      target_warp_fraction = -1;
    }
  }  
}

function animate_warp_auto(p_warp_max, delay) {
  warp_max = p_warp_max;
  setTimeout(function() {
    warp_max = p_warp_max;
    target_warp_fraction = 0.5;
    animate_warp();
  }, delay);
}


// -------------------------
function real_warp() {
  target_warp_fraction = 1.0/warp_max;
  animate_warp();
}



// -------------------------
function palette(scalar, field) {
 if (scalar < 0) {
   scalar = 0;
 } else if (scalar > 1) {
   scalar = 1;
 }

/* 
 n = $("#legend_intervals").val();
 if (n != "" && Number(n) > 2) {
   scalar = (Math.round(scalar*(n-1))+scalar)/n;
 }
*/

 if (field == "sigma" || field == "tresca" ||
     field == "sigma1" || field == "sigma2" || field == "sigma3" ||
     field == "sigmax" || field == "sigmay" || field == "sigmaz" ||
     field == "tauxy" || field == "tauyz" || field == "tauzx") {

   a = 0.5;
   r = a*scalar + (1-a)*Math.cos((scalar-0.75)*Math.PI);
   g = 0.2*a + Math.cos((scalar-0.50)*Math.PI);
   b = a*(1-scalar) + (1-a)*Math.cos((scalar-0.25)*Math.PI);

   r = (r<0)?0:r;
   g = (g<0)?0:g;
   b = (b<0)?0:b;
   
 } else if (field == "uvw") { 
    
   if (modal == false) {  
     r = scalar;
     g = 1-scalar;
     b = 0.5;
/*     
     r = 0.5;
     g = 1-scalar;
     b = scalar;
*/  
   } else {
     if (scalar < 1.0/6.0) {
       r = 1;
       g = 0;
       b = 6.0*(scalar-0);
     } else if (scalar < 2.0/6.0) {
       r = 1-6.0*(scalar-1.0/6.0);
       g = 0;
       b = 1;
     } else if (scalar < 3.0/6.0) {
       r = 0;
       g = 6.0*(scalar-2.0/6.0);
       b = 1;
     } else if (scalar < 4.0/6.0) {
       r = 0;
       g = 1;
       b = 1-6.0*(scalar-3.0/6.0);
     } else if (scalar < 5.0/6.0) {
       r = 6.0*(scalar-4.0/6.0);
       g = 1;
       b = 0;
     } else  {
       r = 1;
       g = 1-6.0*(scalar-5.0/6.0);
       b = 0;
     }
   
     if (scalar < 0.1) {
       b = 1-10.0*scalar;
       g = 1-10.0*scalar;
     }
   }  
  
   
 
 } else if (field == "u" || field == "v" || field == "w") {

    if (scalar < 0.25) {
      r = 0;
      g = scalar;
      b = 0.5+0.5*(scalar)/0.25;
    } else if (scalar < 0.50) {
      r = (scalar-0.25)/0.25;
      g = 0.25+0.75*(scalar-0.25)/0.25;
      b = 1;
    } else if (scalar < 0.75) {
      r = 1;
      g = 1-0.75*(scalar-0.5)/0.25;
      b = 1-(scalar-0.5)/0.25;
    } else {
      r = 1-0.5*(scalar-0.75)/0.25;
      g = 1-scalar;
      b = 0;
    }
 
 } else if (field == "temperature") {
     
    if (scalar < 0.25) {
      r = 0;
      g = scalar/0.25;
      b = 1;
    } else if (scalar < 0.50) {
      r = 0;
      g = 1;
      b = 1-(scalar-0.25)/0.25;
    } else if (scalar < 0.75) {
      r = (scalar-0.50)/0.25;
      g = 1;
      b = 0;
    } else {
      r = 1;
      g = 1-(scalar-0.75)/0.25;
      b = 0;
    }
     
     
 }

 // TODO: printf-formatted
 return r + " " + g + " " + b;

}

function cad_update_colors() {
  for (let i = 1; i <= n_faces; i++) {
    let face_bc = bc_groups_get(i, 2);
    document.getElementById("cad__matface"+i).diffuseColor = (face_bc == 0) ? color["base"] : color["bc_" + face_bc];
  }
  for (let i = 1; i <= n_edges; i++) {
    let edge_bc = bc_groups_get(i, 1);
    document.getElementById("cad__matedge"+i).emissiveColor = (edge_bc == 0) ? "0 0 0" : color["bc_" + edge_bc];
  }
}

function bc_groups_get(id, dim = 0) {
  for (let i = 1; i <= n_bcs; i++) {
    let entities_dim = document.getElementById("bc_what_"+i).value;
    let text = document.getElementById("text_bc_"+i+"_groups");
    if ((dim == 0 || entities_dim == dim) && text != null) {
      if (text.value == id) {
        return i;
      } else {
        const split_text = text.value.split(",");
        for (let j = 0; j < split_text.length; j++) {
          if (split_text[j] == id) {
            return i;
          }
        }
      }
    }
  }
  return 0;
}

function bc_update_from_text(bc) {
  cad_update_colors();
  let text = document.getElementById("text_bc_"+bc+"_groups");
  ajax2problem(text.name.replace("groups", geo_entity[current_dim]), text.value);
}


function bc_group_add(bc, group) {
  let text = document.getElementById("text_bc_"+bc+"_groups");
  let new_text = text.value;
  if (text != null) {
    if (text.value == "") {
      new_text = group;
    } else {
      new_text += "," + group;
    }
    text.value = new_text;
    bc_update_from_text(bc);
  }
}

function bc_group_remove(bc, group) {
  let text = document.getElementById("text_bc_"+bc+"_groups");
  let new_text = "";
  if (text != null) {
    if (text.value == group) {
      new_text = "";
    } else {
      const split_text = text.value.split(",");
      for (let j = 0; j < split_text.length; j++) {
        if (split_text[j] != group) {
          new_text += split_text[j] + ",";
        }
      }
      new_text = new_text.replace(/,(\s+)?$/, '');
    }
    text.value = new_text;
    bc_update_from_text(bc);
  }
}


// faces
function face_over(id) {
  if (current_dim == 2 && current_bc != 0) {
    canvas.runtime.getCanvas().style.cursor = "pointer";
    var face_bc = bc_groups_get(id, 2);
    if (face_bc == 0 || face_bc == current_bc) {
      c = color["bc_" + current_bc];
      col_string = 0.5*(1+c[0]) + " " + 0.5*(1+c[1]) + " " + 0.5*(1+c[2]);
    } else {
      // TODO: named clash
      col_string = "1 0 0";
    }
    document.getElementById("cad__matface"+id).diffuseColor = col_string;
  }
}

function face_out(id) {
  if (current_dim == 2 && current_bc != 0) {
    canvas.runtime.getCanvas().style.cursor = "";
    var face_bc = bc_groups_get(id, 2);
    if (face_bc == 0) {
      c = color["base"];
    } else {
      // TODO: named clash
      c = color["bc_" + face_bc];
    }
    document.getElementById("cad__matface"+id).diffuseColor = c[0] + ' ' + c[1] + ' ' + c[2];
  }
}


function face_click(face_id) {
  if (current_dim == 2 && current_bc != 0) {
    var face_bc = bc_groups_get(face_id, 2);
    if (face_bc == 0) {
      // theseus_log("add " + face_id);
      bc_group_add(current_bc, face_id);
    } else if (face_bc == current_bc) {
      bc_group_remove(face_bc, face_id);
    } else {
      c = [1, 0, 0];
      document.getElementById("cad__matface"+id).diffuseColor = c[0] + ' ' + c[1] + ' ' + c[2];
    }
  }
}


// edges
function edge_over(id) {
  if (current_dim == 1 && current_bc != 0) {
    canvas.runtime.getCanvas().style.cursor = "crosshair";
    var edge_bc = bc_groups_get(id, 1);
    if (edge_bc == 0 || edge_bc == current_bc) {
      c = color["bc_" + current_bc];
      col_string = 0.5*(1+c[0]) + " " + 0.5*(1+c[1]) + " " + 0.5*(1+c[2]);
    } else {
      // TODO: named clash
      col_string = "1 0 0";
    }
    document.getElementById("cad__matedge"+id).emissiveColor = col_string;
  }
}

function edge_out(id) {
  if (current_dim == 1 && current_bc != 0) {
    canvas.runtime.getCanvas().style.cursor = "";
    var edge_bc = bc_groups_get(id, 1);
    if (edge_bc == 0) {
      c = "0 0 0";
    } else {
      // TODO: named clash
      c = color["bc_" + edge_bc];
    }
    document.getElementById("cad__matedge"+id).emissiveColor = c;
  }
}


function edge_click(edge_id) {
  if (current_dim == 1 && current_bc != 0) {
    var edge_bc = bc_groups_get(edge_id, 1);
    if (edge_bc == 0) {
      // theseus_log("add " + edge_id);
      bc_group_add(current_bc, edge_id);
    } else if (edge_bc == current_bc) {
      bc_group_remove(edge_bc, edge_id);
    } else {
      c = [1, 0, 0];
      document.getElementById("cad__matedge"+id).emissiveColor = c[0] + ' ' + c[1] + ' ' + c[2];
    }
  }
}

function mesh_field_add(field) {
  bootstrap_flex("row_"+field);
  // TODO: remove the added field from the select
  select_add_mesh_field.value = "add";
  ajax2mesh(field, document.getElementById("text_"+field).value);
}

function mesh_field_update(id, val) {
  document.getElementById("text_"+id).value = val;
  document.getElementById("range_"+id).value = val;
}


function mesh_field_remove(field) {
  theseus_log("remove mesh field " + field);
  ajax2mesh(field, "remove");
  return change_step(1); 
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


function geo_edit() {
  bootstrap_hide("geo_error_message");
  bootstrap_hide("btn_geo_back");
  bootstrap_hide("btn_geo_edit");
  bootstrap_hide("div_geo_html");

  text_geo_edit.value = plain_geo;
  
  bootstrap_block("btn_geo_cancel");
  bootstrap_block("btn_geo_accept");
  bootstrap_block("div_geo_edit");
}
  

function geo_cancel() {
  bootstrap_hide("geo_error_message");
  bootstrap_hide("btn_geo_cancel");
  bootstrap_hide("btn_geo_accept");
  bootstrap_hide("div_geo_edit");

  text_geo_edit.value = plain_geo;
  
  bootstrap_block("btn_geo_back");
  bootstrap_block("btn_geo_edit");
  bootstrap_block("div_geo_html");
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

function fee_edit() {
  bootstrap_hide("fee_error_message");
  bootstrap_hide("btn_fee_back");
  bootstrap_hide("btn_fee_edit");
  bootstrap_hide("div_fee_html");

  text_fee_edit.value = plain_fee;
  
  bootstrap_block("btn_fee_cancel");
  bootstrap_block("btn_fee_accept");
  bootstrap_block("div_fee_edit");
}
  

function fee_cancel() {
  bootstrap_hide("fee_error_message");
  bootstrap_hide("btn_fee_cancel");
  bootstrap_hide("btn_fee_accept");
  bootstrap_hide("div_fee_edit");

  text_fee_edit.value = plain_fee;
  
  bootstrap_block("btn_fee_back");
  bootstrap_block("btn_fee_edit");
  bootstrap_block("div_fee_html");
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


function bc_add(type = "") {
  n_bcs++;
  var myCollapse = document.getElementById("collapse_bc_"+n_bcs)
  var bsCollapse = new bootstrap.Collapse(myCollapse, {
    toggle: true
  })
  
  bc_update_type(n_bcs, type);
  bootstrap_block("div_bc_" + n_bcs);
  cad_update_colors();
  current_dim = 2;
}

function bc_remove(i) {
  ajax2problem("bc_"+i+"_remove", "remove");
  change_step(2);
}

function bc_update_type(i, type) {
  theseus_log("update " + i + " " + type);
  bc_hide_all(i);
  bootstrap_block("bc_value_"+i+"_"+type);
}


function bc_change_filter(i, what) {
  let text = document.getElementById("text_bc_"+i+"_groups");
  text.value = "";
  // cad_update_colors();
  ajax2problem(text.name.replace("groups", geo_entity[current_dim]), text.value);
  
  current_dim = Number(what);
  theseus_log("current_dim = "+i);
  
}
  
