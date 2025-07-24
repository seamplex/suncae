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
    const matface = document.getElementById("cad__matface"+i);
    if (matface != null) {
      matface.diffuseColor = (face_bc == 0) ? color["base"] : color["bc_" + face_bc];
    }  
  }
  for (let i = 1; i <= n_edges; i++) {
    let edge_bc = bc_groups_get(i, 1);
    const matedge = document.getElementById("cad__matedge"+i);
    if (matedge != null) {
      matedge.emissiveColor = (edge_bc == 0) ? "0 0 0" : color["bc_" + edge_bc];
    }
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
  bootstrap_block("bc_value_" + i + "_" + type);
  // THINK
  const combo = document.getElementById("bc_what_"+i);
  if (combo != null && combo.value == "fixture") {
//    alert("mongocho");
    bc_fixture_update(i);
  }
}


function bc_change_filter(i, what) {
  let text = document.getElementById("text_bc_"+i+"_groups");
  text.value = "";
  // cad_update_colors();
  ajax2problem(text.name.replace("groups", geo_entity[current_dim]), text.value);
  
  current_dim = Number(what);
  theseus_log("current_dim = "+i);
  
}

function intersection_location(i, location, radius) {
  const intersection = document.getElementById("intersection"+i);
  intersection.setAttribute("translation", location);
  const sphere = document.getElementById("sphere"+i);
  sphere.setAttribute("radius", radius);
}


function intersection_radius(i, radius) {
  const sphere = document.getElementById("sphere"+i);
  sphere.setAttribute("radius", radius);
}
