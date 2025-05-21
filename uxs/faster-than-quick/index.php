<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

// TODO: move to cad.php?
if (($cad_json = file_get_contents("../data/{$username}/cads/{$case["cad"]}/cad.json")) == false) {
  return_error("cannot find cad {$case["cad"]}");
}

if (($cad = json_decode($cad_json, true)) == null) {
  return_error("cannot decode cad {$id}");
}
?>
<!doctype html>
<html lang="en" class="h-100">
<head>
 <meta charset="utf-8">
 <title>Faster-than-quick :: <?=$case["name"]?></title>
 
<!--  TODO: preview and meta data for sharing the url -->
  
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <link rel="stylesheet" href="css/faster-than-quick/bootstrap.min.css">
 <link rel="stylesheet" href="css/faster-than-quick/bootstrap-icons.min.css">
 <link rel="stylesheet" href="css/faster-than-quick/ftq.css">
 <link rel="stylesheet" href="css/faster-than-quick/highlight.css">
 <link rel="stylesheet" href="css/faster-than-quick/x3dom.css">
 <link rel="stylesheet" href="css/faster-than-quick/katex.min.css">
 
</head>
<body>

<?php
include("about.php");
?>

<div class="modal fade" id="modal_geo" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modal_geo_label" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
  <div class="modal-content">
   <div class="modal-header">
    <h1 class="modal-title fs-5" id="modal_geo_label"><a href="http://gmsh.info/" target="blank">Gmsh</a> input</h1>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
   </div>
   <div class="modal-body">

    <div id="div_geo_html"></div>
    
    <div id="div_geo_edit" class="form-floating d-none">
     <textarea class="form-control" id="text_geo_edit" style="height: 200px; font-family:monospace;"></textarea>
     <label for="text_geo_edit" style="font-family:monospace">Merge "../../cads/<?=$case["cad"]?>/cad.xao";</label>
    </div>
    
    <div id="geo_error_message" class="small alert alert-danger d-none"></div>

   </div>
   <div class="modal-footer">
    <button type="button" id="btn_geo_back" class="btn btn-primary" data-bs-dismiss="modal">
     <i class="bi bi-arrow-left me-2"></i>
     Back
    </button>
    <button type="button" id="btn_geo_edit" class="btn btn-secondary" onclick="geo_edit()">
     <i class="bi bi-pencil-fill me-2"></i>
     Edit
    </button>
    <button type="button" id="btn_geo_cancel" class="btn btn-danger d-none" onclick="geo_cancel()">
     <i class="bi bi-x-circle-fill me-2"></i>
     Cancel
    </button>
    <button type="button" id="btn_geo_accept" class="btn btn-success d-none" onclick="geo_save()">
     <i class="bi bi-check-square-fill me-2"></i>
     Accept
    </button>
    <button type="button" class="btn btn-info">
     <i class="bi bi-question-circle me-2"></i>
     Help
    </button>
   </div>
  </div>
 </div>
</div>

<div class="modal fade" id="modal_log" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modal_log_label" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
  <div class="modal-content">
   <div class="modal-header">
    <h1 class="modal-title fs-5" id="modal_log_label"><a href="http://gmsh.info/" target="blank">Gmsh</a> input</h1>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
   </div>
   <div class="modal-body">

    <pre id="div_err_html" class="alert alert-danger"></pre>
    <pre id="div_log_html" class="alert alert-info"></pre>

   </div>
   <div class="modal-footer">
    <button type="button" id="btn_log_back" class="btn btn-primary" data-bs-dismiss="modal">
     <i class="bi bi-arrow-left me-2"></i>
     Back
    </button>
   </div>
  </div>
 </div>
</div>

<div class="modal fade" id="modal_fee" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modal_fee_label" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
  <div class="modal-content">
   <div class="modal-header">
    <h1 class="modal-title fs-5" id="modal_fee_label"><a href="#" target="blank">FeenoX</a> input</h1>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
   </div>
   <div class="modal-body">

    <div id="div_fee_html"></div>

    <div id="div_fee_edit" class="form-floating d-none">
     <textarea class="form-control" id="text_fee_edit" style="height: 400px; font-family:monospace;"></textarea>
<!--      <label for="text_fee_edit" id="text_fee_edit_header" style="font-family:monospace"></label> -->
    </div>

    <div id="fee_error_message" class="small alert alert-danger d-none"></div>

   </div>
   <div class="modal-footer">
    <button type="button" id="btn_fee_back" class="btn btn-primary" data-bs-dismiss="modal">
     <i class="bi bi-arrow-left me-2"></i>
     Back
    </button>
    <button type="button" id="btn_fee_edit" class="btn btn-secondary" onclick="fee_edit()">
     <i class="bi bi-pencil-fill me-2"></i>
     Edit
    </button>
    <button type="button" id="btn_fee_cancel" class="btn btn-danger d-none" onclick="fee_cancel()">
     <i class="bi bi-x-circle-fill me-2"></i>
     Cancel
    </button>
    <button type="button" id="btn_fee_accept" class="btn btn-success d-none" onclick="fee_save()">
     <i class="bi bi-check-square-fill me-2"></i>
     Accept
    </button>
    <button type="button" class="btn btn-info">
     <i class="bi bi-question-circle me-2"></i>
     Help
    </button>
   </div>
  </div>
 </div>
</div>







 <div class="container-fluid">
  <div class="row" style="height: 100vh; max-height: 100vh">

<!-- ============================================================================================================ -->
   <div id="left" class="col-12   d-none
                         col-xs-7
                         col-md-6
                         col-lg-5 d-lg-block
                         col-xl-4
                         col-xxl-3
                         bg-light p-1 overflow-auto">
    <div class="row m-1 p-1 d-block d-sm-none">
     <div class="btn-group" role="group">
      <button class="btn btn-outline-info w-100" onclick="toggle_toolbar('left', 'xs')">
       <i class="bi bi-box-arrow-left me-2"></i>Back to 3D view
      </button>
      <button class="btn btn-outline-info">
       <i class="bi bi-question-circle"></i>
      </button>
     </div>
    </div>

    <div class="collapse show my-5 py-5 text-center" id="collapse_loading">
     <div class="spinner-border text-primary">
     </div>
    </div> 

    <div class="collapse" id="collapse_leftcol">
    </div>
   </div>
<!-- ============================================================================================================ -->



<?php
  $length["x"] = $cad["xmax"]-$cad["xmin"];
  $length["y"] = $cad["ymax"]-$cad["ymin"];
  $length["z"] = $cad["zmax"]-$cad["zmin"];
  $max_delta = max($length["x"], $length["y"], $length["z"]);
  
  $factor = 0.2;
  $characteristic_length = $factor * max($cad["xmax"]-$cad["xmin"],
                                         $cad["ymax"]-$cad["ymin"],
                                         $cad["zmax"]-$cad["zmin"]);
  
?>

<script type="text/javascript">

n_solids = <?=$cad["solids"]?>;
n_faces = <?=$cad["faces"]?>;
n_edges = <?=$cad["edges"]?>;
n_vertices = <?=$cad["vertices"]?>;

// esta se usa para mirar mas o menos el tamaño de los puntos
var lc_approx = <?=$cad["area"] > 0 ? sprintf("%.4g", $cad["volume"]/$cad["area"]) : 1 ?>;

var xmin = <?=$cad["xmin"]?>;
var xmax = <?=$cad["xmax"]?>;
var ymin = <?=$cad["ymin"]?>;
var ymax = <?=$cad["ymax"]?>;
var zmin = <?=$cad["zmin"]?>;
var zmax = <?=$cad["zmax"]?>;

var characteristic_length = <?=$characteristic_length?>;

var max_delta = <?=$max_delta?>;

//var entity = json_encode($cad["entity"]);
var solid_base_color = <?=json_encode($cad["color"])?>;
//var color = json_encode($color);
</script>


<!-- ............................................................................................................ -->
   <div id="right" class="col-12   d-none
                          col-xs-6
                          col-md-5 
                          col-lg-4 order-lg-3
                          col-xl-3
                          col-xxl-2 d-xxl-block 
                          bg-light p-1 overflow-auto">

    <div class="text-center">
     <button class="btn btn-outline-info my-3 d-block d-sm-none" onclick="toggle_toolbar('right', 'xs')">
      <i class="bi bi-box-arrow-left me-1"></i>Back to 3D view
     </button>
    </div> 

    <div class="text-center my-2">
     <div class="btn-group" role="group">
      <a href="new/" class="btn btn-success" role="button">
       <i class="bi bi-plus-circle me-2"></i>New case
      </a>
      <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
       <i class="bi bi-sun me-2"></i>SunCAE
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
       <li><a class="dropdown-item" href="https://github.com/seamplex/suncae">What's this?</a></li>
       <li><a class="dropdown-item" href="#">FAQs</a></li>
       <li><a class="dropdown-item" href="#">Tutorials</a></li>
       <li><hr class="dropdown-divider"></li>
       <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modal_about">About</a></li>
      </ul>
     </div>
    </div> 

                         
    <div class="accordion" id="accordion_right">
     
     <div class="accordion-item">
      <h2 class="accordion-header" id="heading_display">
       <button class="accordion-button text-white bg-info py-2  collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_display">
        Display
       </button>
      </h2>
      <div id="collapse_display" class="accordion-collapse collapse show" data-bs-parent="#accordion_right">
       <div class="accordion-body bg-white">
       
        <div class="row">
         <div class="col-3 text-end p-1">
          <label for="range_axes" class="form-label">Axes</label>
         </div> 
         <div class="col-9 p-1">
          <input type="range" class="form-range text-end" id="range_axes" oninput="big_axes(this.value)" min="0" max="1.5" step="0.01" value="1">
         </div> 
        </div>
        <div class="row">
         <div class="col-3 text-end p-1">
          <label for="range_cad_faces" class="form-label">Faces</label>
         </div> 
         <div class="col-9 p-1">
          <input type="range" class="form-range" id="range_cad_faces" oninput="cad_faces(this.value)" min="0" max="1" step="0.01" value="1">
         </div> 
        </div>

        <div class="row">
         <div class="col-3 text-end p-1">
          <label for="range_cad_edges" class="form-label">Edges</label>
         </div> 
         <div class="col-9 p-1">
          <input type="range" class="form-range" id="range_cad_edges" oninput="cad_edges(this.value)" min="0" max="8" step="1" value="1">
         </div> 
        </div>

        <div class="row">
         <div class="col-3 text-end p-1">
          <label for="range_cad_vertices" class="form-label">Vertices</label>
         </div> 
         <div class="col-9 p-1">
          <input type="range" class="form-range" id="range_cad_vertices" oninput="cad_vertices(this.value)" min="0" max="1.5" step="0.01" value="0">
         </div> 
        </div>

        <!-- TODO: send to yaml via ajax          -->
        <div class="row">
         <div class="col-3 text-end p-1">
          <label id="label_mesh_triangles" for="check_mesh_triangles" class="form-label">Mesh</label>
         </div> 
         <div class="col-1 text-center p-1">
          <label id="label_mesh_triangles" for="check_mesh_triangles" class="form-label"><i class="bi bi-triangle-fill"></i></label>
         </div> 
         <div class="col-2 p-1">
          <div class="form-check form-switch">
           <input class="form-check-input" type="checkbox" id="check_mesh_triangles" onchange="mesh_triangles('toggle')">
          </div> 
         </div>
         
         <div class="col-3 text-end p-1">
          <label id="label_mesh_lines" for="check_mesh_lines" class="form-label">Mesh</label>
         </div> 
         <div class="col-1 text-center p-1">
          <label id="label_mesh_lines" for="check_mesh_lines" class="form-label"><i class="bi bi-triangle"></i></label>
         </div> 
         <div class="col-2 p-1">
          <div class="form-check form-switch">
           <input class="form-check-input" type="checkbox" id="check_mesh_lines" onchange="mesh_lines('toggle')">
          </div> 
         </div> 

         <div class="col-3 text-end p-1">
          <label id="label_results_lines" for="check_bounding_box" class="form-label">Bounding</label>
         </div> 
         <div class="col-1 text-end p-1">
          <label id="label_results_lines" for="check_bounding_box" class="form-label"><i class="bi bi-box"></i></label>
         </div> 
         <div class="col-2 p-1">
          <div class="form-check form-switch">
           <input class="form-check-input" type="checkbox" id="check_bounding_box" onchange="bounding_box('toggle')">
          </div> 
         </div> 
         
<!--    TODO: each problem should have "has warp" -->
         <div class="col-3 text-end p-1 <?=($problem == "mechanical")?"":"d-none"?>">
          <label id="label_results_lines" for="check_results_lines" class="form-label">Warped</label>
         </div> 
         <div class="col-1 text-end p-1 <?=($problem == "mechanical")?"":"d-none"?>">
          <label id="label_results_lines" for="check_results_lines" class="form-label"><i class="bi bi-box-arrow-up-right"></i></label>
         </div> 
         <div class="col-2 p-1 <?=($problem == "mechanical")?"":"d-none"?>">
          <div class="form-check form-switch">
           <input class="form-check-input" type="checkbox" id="check_results_lines" onchange="results_lines('toggle')">
          </div> 
         </div> 
                 
        </div>
<!--
        <div class="row">
         <div class="col-3 text-end p-1">
          <label for="range_mesh_errors" class="form-label text-muted">Errors</label>
         </div> 
         <div class="col-9 p-1">
          <input type="range" class="form-range" disabled id="range_mesh_errors" oninput="mesh_errors(this.value)" min="0" max="1" step="0.01" value="0.5">
         </div> 
        </div>
        <p class="card-text">Max. markers</p>
-->


        <button class="btn btn-primary" onClick="fit_all_view()">Fit all</button>

<?php
include("small_axes.html");
?>
        

       </div>
      </div>
     </div>
     

     
     
     <div class="accordion-item">
      <h2 class="accordion-header" id="heading_snapshots">
       <button class="accordion-button text-white bg-info py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_snapshots">
        Snapshots
       </button>
      </h2>
      <div id="collapse_snapshots" class="accordion-collapse collapse" data-bs-parent="#accordion_right">
       <div class="accordion-body bg-white">
        <p class="card-text">xxx</p>
       </div>
      </div>
     </div>

     
     
     
     <div class="accordion-item">
      <h2 class="accordion-header" id="heading_clip">
       <button class="accordion-button text-white bg-info py-2 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_clip">
        Clipping plane
       </button>
      </h2>
      <div id="collapse_clip" class="accordion-collapse collapse" data-bs-parent="#accordion_right">
       <div class="accordion-body bg-white p-2">
        <div class="d-flex mb-2">
         <div class="btn-group btn-group-toggle w-75">
        
          <input type="radio" class="btn-check" name="clip_plane" id="clip_radio" autocomplete="off" checked onclick="set_clip_plane('')">
          <label class="btn btn-outline-primary" for="clip_radio"><small>None</small></label>

          <input type="radio" class="btn-check" name="clip_plane" id="clip_x_radio" autocomplete="off" onclick="set_clip_plane('x', <?=$cad["cog"][0]?>)">
          <label class="btn btn-outline-primary" for="clip_x_radio"><small>x</small></label>

          <input type="radio" class="btn-check" name="clip_plane" id="clip_y_radio" autocomplete="off" onclick="set_clip_plane('y', <?=$cad["cog"][1]?>)">
          <label class="btn btn-outline-primary" for="clip_y_radio"><small>y</small></label>

          <input type="radio" class="btn-check" name="clip_plane" id="clip_z_radio" autocomplete="off" onclick="set_clip_plane('z', <?=$cad["cog"][2]?>)">
          <label class="btn btn-outline-primary" for="clip_z_radio"><small>z</small></label>
          
         </div>
         
         <div class="btn-group btn-group-toggle w-25 pt-2 justify-content-center">
          <div class="form-check form-switch">
           <input class="form-check-input" type="checkbox" id="clip_revert_checkbox" disabled onchange="set_clip_offset(document.getElementById('clip_offset_range').value)"  data-bs-toggle="tooltip" title="Invert the direction of the clipping plane">
          </div> 
         </div>
        </div>

        <input type="range" class="form-range" id="clip_offset_range" disabled oninput="set_clip_offset(this.value)" min="0" max="100" step="1" value="50"  data-bs-toggle="tooltip" title="Offset of the clipping plane">
        
       </div>
      </div>
     </div>
    </div>

<!--  TODO: dismiss button    -->
    <div id="warning_message" class="small alert alert-dismissible alert-warning d-none"></div>
    <div id="error_message" class="small alert alert-dismissible alert-danger d-none"></div>
   </div> 

<!-- ............................................................................................................ -->



<!-- ____________________________________________________________________________________________________________ -->
   <div class="col p-0 m-0 mh-100 position-relative">
    <x3d id="canvas" class="bg-white border-0 p-0 m-0 w-100 h-100"
     xmlns:xsd="http://www.w3.org/2001/XMLSchema-instance" version="3.3"
     xsd:noNamespaceSchemaLocation="http://www.web3d.org/specifications/x3d-3.3.xsd"
     showProgress="true" showStat="false" showLog="false">
     <Scene>
     
      <!--  TODO: bounding box with size in mms?      -->
      <!--  TODO: named viewpoints     -->
      <OrthoViewpoint id="main_viewpoint"
        position="<?=$cad["position"]?>"
        orientation="<?=$cad["orientation"]?>"
        centerOfRotation="<?=$cad["centerOfRotation"]?>"
        fieldOfView="<?=$cad["fieldOfView"]?>"
        zFar="1e6">
      </OrthoViewpoint>
      <Group id="solid_plus_clip">
      
       <!-- CAD -->
       <Inline id="geometry" nameSpaceName="cad" mapDEFToID="true" url="cad.php?id=<?=$id?>" onload="geo_ready()"></Inline>
       <!-- mesh nodes -->
       <Coordinate id="nodes" DEF="nodes"></Coordinate>
       
       <!-- mesh surfaces_edges -->
       <Shape id="surfaces_edges">
        <Appearance><Material emissiveColor="0 0 0"></Material></Appearance>
        <IndexedLineSet id="surfaces_edges_set">
         <Coordinate USE="nodes"></Coordinate>
        </IndexedLineSet> 
       </Shape>
       
       <!-- mesh surfaces_faces -->
       <Group id="surfaces_faces"></Group> 
       
       <!-- results -->
       <Shape id="results_surfaces_edges" render="false"></Shape>  
       <Shape id="results_surfaces_faces" render="false"></Shape>      

       <ClipPlane id="clip_plane_x" cappingColor="0.7 0 0" cappingStrength="0.5" enabled="false" on="false" plane="1 0 0 0" ></ClipPlane>
       <ClipPlane id="clip_plane_y" cappingColor="0 0.7 0" cappingStrength="0.5" enabled="false" on="false" plane="0 1 0 0" ></ClipPlane>
       <ClipPlane id="clip_plane_z" cappingColor="0 0 0.7" cappingStrength="0.5" enabled="false" on="false" plane="0 0 1 0" ></ClipPlane>
      </Group>
 
      <group id="axes">
       <!--  x  -->
       <shape id="axis_line_x" isPickable="false">
        <IndexedLineSet index="0 1 -1">
         <coordinate id="axis_line_coord_x" point="<?=$cad["xmin"]-$characteristic_length?> 0 0, <?=$cad["xmax"]+$characteristic_length?> 0 0"></coordinate>
        </IndexedLineSet>
        <Appearance id="red">
         <Material diffuseColor="0 0 0" emissiveColor="0.7 0 0"></Material>
         <DepthMode readOnly="false"></DepthMode>
<!--          <LineProperties linewidthScaleFactor="2"></LineProperties> -->
        </Appearance>
       </shape>
       <Transform id="axis_arrow_x" translation="<?=$cad["xmax"]+$characteristic_length?> 0 0">
        <Transform rotation="0 0 1 <?=-M_PI/2?>">
         <Shape isPickable="false">
          <Cone id="axis_cone_x" bottomRadius="<?=0.1*$characteristic_length?>" height="<?=0.5*$characteristic_length?>" subdivision="16"></Cone>
          <Appearance USE="red"></Appearance>
         </Shape>
        </Transform>
       </Transform>
       
       <!--  y  -->
       <shape isPickable="false" id="axis_line_y">
        <IndexedLineSet index="0 1 -1">
         <coordinate id="axis_line_coord_y" point="0 <?=$cad["ymin"]-$characteristic_length?> 0, 0 <?=$cad["ymax"]+$characteristic_length?> 0"></coordinate>
        </IndexedLineSet>
        <Appearance id="green">
         <Material diffuseColor="0 0 0" emissiveColor="0 0.7 0"></Material>
         <DepthMode readOnly="false"></DepthMode>
<!--          <LineProperties linewidthScaleFactor="2"></LineProperties> -->
        </Appearance>
       </shape>
       <Transform id="axis_arrow_y" translation="0 <?=$cad["ymax"]+$characteristic_length?> 0">
        <Transform rotation="0 1 0 <?=-M_PI/2?>">
         <Shape isPickable="false">
          <Cone id="axis_cone_y" bottomRadius="<?=0.1*$characteristic_length?>" height="<?=0.5*$characteristic_length?>" subdivision="16"></Cone>
          <Appearance USE="green"></Appearance>
         </Shape>
        </Transform>
       </Transform>
       
       <!--  z  -->
       <shape isPickable="false" id="axis_line_z">
        <IndexedLineSet index="0 1 -1">
         <coordinate id="axis_line_coord_z" point="0 0 <?=$cad["zmin"]-$characteristic_length?>, 0 0 <?=$cad["zmax"]+$characteristic_length?>"></coordinate>
        </IndexedLineSet>
        <Appearance id="blue">
         <Material diffuseColor="0 0 0" emissiveColor="0 0 0.7"></Material>
         <DepthMode readOnly="false"></DepthMode>
<!--          <LineProperties linewidthScaleFactor="2"></LineProperties> -->
        </Appearance>
       </shape>
       <Transform id="axis_arrow_z" translation="0 0 <?=$cad["zmax"]+$characteristic_length?>">
        <Transform rotation="1 0 0 <?=+M_PI/2?>">
         <Shape isPickable="false">
          <Cone id="axis_cone_z" bottomRadius="<?=0.1*$characteristic_length?>" height="<?=0.5*$characteristic_length?>" subdivision="16"></Cone>
          <Appearance USE="blue"></Appearance>
         </Shape>
        </Transform>
       </Transform>
       
      </group>
      
      <group id="bbox" render="false">
       <Shape id="bbox_x_shape">
        <IndexedLineSet index="0 1 -1">
         <coordinate id="bbox_x_is" point="<?=$cad["xmin"]?> <?=$cad["ymin"]?> <?=$cad["zmin"]?>, <?=$cad["xmax"]?> <?=$cad["ymin"]?> <?=$cad["zmin"]?>"></coordinate>
        </IndexedLineSet>
        <Appearance USE='red'></Appearance>
       </Shape> 
       <Transform translation='<?=$cad["xmax"]+0.1*$characteristic_length?> <?=$cad["ymin"]-0.2*$characteristic_length?> <?=$cad["zmin"]-0.2*$characteristic_length?>'>
        <Billboard axisOfRotation='0 0 0'>
         <Transform translation='0 0 0' scale='<?=0.2*$characteristic_length?> <?=0.2*$characteristic_length?> <?=0.2*$characteristic_length?>'>
          <Shape isPickable='false' DEF='bbox_label_x'>
           <Text string='<?=sprintf("%.1f mm", $cad["xmax"]-$cad["xmin"])?>' solid='false'><fontstyle family="'Serif'" style='italic'></fontstyle></Text>
           <Appearance USE='red'></Appearance>
          </Shape>
         </Transform>
        </Billboard>
       </Transform>
       
       
       <Shape id="bbox_y_shape">
        <IndexedLineSet index="0 1 -1">
         <coordinate id="bbox_x_is" point="<?=$cad["xmin"]?> <?=$cad["ymin"]?> <?=$cad["zmin"]?>, <?=$cad["xmin"]?> <?=$cad["ymax"]?> <?=$cad["zmin"]?>"></coordinate>
        </IndexedLineSet>
        <Appearance USE='green'></Appearance>
       </Shape> 
       <Transform translation='<?=$cad["xmin"]-0.2*$characteristic_length?> <?=$cad["ymax"]+0.1*$characteristic_length?> <?=$cad["zmin"]-0.2*$characteristic_length?>'>
        <Billboard axisOfRotation='0 0 0'>
         <Transform translation='0 0 0' scale='<?=0.2*$characteristic_length?> <?=0.2*$characteristic_length?> <?=0.2*$characteristic_length?>'>
          <Shape isPickable='false' DEF='bbox_label_y'>
           <Text string='<?=sprintf("%.1f mm", $cad["ymax"]-$cad["ymin"])?>' solid='false'><fontstyle family="'Serif'" style='italic'></fontstyle></Text>
           <Appearance USE='green'></Appearance>
          </Shape>
         </Transform>
        </Billboard>
       </Transform>
       
       
       <Shape id="bbox_z_shape">
        <IndexedLineSet index="0 1 -1">
         <coordinate id="bbox_z_is" point="<?=$cad["xmin"]?> <?=$cad["ymin"]?> <?=$cad["zmin"]?>, <?=$cad["xmin"]?> <?=$cad["ymin"]?> <?=$cad["zmax"]?>"></coordinate>
        </IndexedLineSet>
        <Appearance USE='blue'></Appearance>
       </Shape>
       <Transform translation='<?=$cad["xmin"]-0.2*$characteristic_length?> <?=$cad["ymin"]-0.2*$characteristic_length?> <?=$cad["zmax"]+0.1*$characteristic_length?>'>
        <Billboard axisOfRotation='0 0 0'>
         <Transform translation='0 0 0' scale='<?=0.2*$characteristic_length?> <?=0.2*$characteristic_length?> <?=0.2*$characteristic_length?>'>
          <Shape isPickable='false' DEF='bbox_label_z'>
           <Text string='<?=sprintf("%.1f mm", $cad["zmax"]-$cad["zmin"])?>' solid='false'><fontstyle family="'Serif'" style='italic'></fontstyle></Text>
           <Appearance USE='blue'></Appearance>
          </Shape>
         </Transform>
        </Billboard>
       </Transform>
       
      </group>
      
      
     </scene>
    </x3d>
    
    
    <nav class="navbar navbar-light fixed-top position-absolute mt-0 pt-0" style="--bs-breadcrumb-divider: '>';">
     <div class="col-md-auto mx-1 px-1 text-start">

      <button type="button" class="btn btn-sm btn-outline-primary m-0 p-1 d-block d-sm-none d-md-none d-lg-none d-xl-none d-xxl-none" id="button_toggle_left_scene_xs" onclick="toggle_toolbar('left', 'xs')">
       <i class="bi bi-pencil-square"></i>
      </button>
      <button type="button" class="btn btn-sm btn-outline-primary m-0 p-1 d-none d-sm-block d-md-none d-lg-none d-xl-none d-xxl-none" id="button_toggle_left_scene_sm" onclick="toggle_toolbar('left', 'sm')">
       <i class="bi bi-pencil-square"></i>
      </button>
      <button type="button" class="btn btn-sm btn-outline-primary m-0 p-1 d-none d-sm-none d-md-block d-lg-none d-xl-none d-xxl-none" id="button_toggle_left_scene_md" onclick="toggle_toolbar('left', 'md')">
       <i class="bi bi-pencil-square"></i>
      </button>
      <button type="button" class="btn btn-sm btn-outline-primary m-0 p-1 d-none d-sm-none d-md-none d-lg-block d-xl-none d-xxl-none" id="button_toggle_left_scene_lg" onclick="toggle_toolbar('left', 'lg')">
       <i class="bi bi-pencil-square"></i>
      </button>
      <button type="button" class="btn btn-sm btn-outline-primary m-0 p-1 d-none d-sm-none d-md-none d-lg-none d-xl-block d-xxl-none" id="button_toggle_left_scene_xl" onclick="toggle_toolbar('left', 'xl')">
       <i class="bi bi-pencil-square"></i>
      </button>
      <button type="button" class="btn btn-sm btn-outline-primary m-0 p-1 d-none d-sm-none d-md-none d-lg-none d-xl-none d-xxl-block" id="button_toggle_left_scene_xxl" onclick="toggle_toolbar('left', 'xxl')">
       <i class="bi bi-pencil-square"></i>
      </button>
     </div>

     <!-- top navigation bar -->
     <div class="col">
      <div class="d-flex justify-content-center">
        <ol class="breadcrumb">

         <li class="breadcrumb-item dropdown dropdown-toggle" role="button">
          <span id="span_nav_dropdown" data-bs-toggle="dropdown">
<!--            <i class="bi globe-americas me-1 <?=($case["visibility"] != "public")?"d-none":"d-inline"?>" id="i_public" data-bs-toggle="tooltip" title="This case is public"></i> -->
           <i class="bi bi-pencil me-1 d-none d-lg-inline" data-bs-toggle="tooltip" title="You have write access"></i>
<!--            <i class="bi bi-menu-button d-inline d-xl-none"></i> -->
           <span class="d-none d-xl-inline" id="span_name"><?=$case["name"]?></span>
          </span>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#" onclick="change_step(-4)">Case properties&hellip;</a></li>
            <li><a class="dropdown-item" href="#" onclick="change_step(+4)">Expert zone&hellip;</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item disabled" href="#" onclick="change_step(-5)">History&hellip;</a></li>
            <li><a class="dropdown-item" href="#" onclick="change_step(+5)">Share&hellip;</a></li>
          </ul>
         </li>
       
         <li id="li_step1" class="breadcrumb-item">
          <span id="badge_step1" class="badge mx-0 bg-dark">Mesh</span>
          <!-- TODO: write in this span if the mesh is valid or not -->
          <span id="span_step1"  class="d-none d-xl-inline text-dark"></span>
         </li>
         <li id="li_step2" class="breadcrumb-item">
          <span id="badge_step2" class="badge mx-0 bg-dark">Problem</span>
          <!-- TODO: write in this span if the problem definition is valid or not -->
          <span id="span_step2"  class="d-none d-xl-inline text-dark"></span>
         </li>
         <li id="li_step3" class="breadcrumb-item">
          <span id="badge_step3" class="badge mx-0 bg-dark">Results</span>
          <!-- TODO: write in this span if there are available results or not -->
          <span id="span_step3"  class="d-none d-xl-inline text-dark"></span>
         </li>
        </ol>
      </div> 
     </div> 
    
     <div class="col-md-auto mx-1 px-1 text-end">
    
      <div class="btn-group text-right" role="group">
       <button type="button" id="button_snapshot_canvas" class="btn btn-outline-secondary btn-sm m-0 p-1 disabled" disabled onclick="take_snapshot()">
        <i class="bi bi-camera"></i>
       </button>

        <button type="button" class="btn btn-outline-primary btn-sm float-right m-0 p-1 d-block d-sm-none d-md-none d-lg-none d-xl-none d-xxl-none"
                id="button_toggle_right_xs"
                onclick="toggle_toolbar('right', 'xs')">
         <i class="bi bi-toggles"></i>
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm float-right m-0 p-1 d-none d-sm-block d-md-none d-lg-none d-xl-none d-xxl-none"
                id="button_toggle_right_sm"
                onclick="toggle_toolbar('right', 'sm')">
         <i class="bi bi-toggles"></i>
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm float-right m-0 p-1 d-none d-sm-none d-md-block d-lg-none d-xl-none d-xxl-none"
                id="button_toggle_right_md"
                onclick="toggle_toolbar('right', 'md')">
         <i class="bi bi-toggles"></i>
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm float-right m-0 p-1 d-none d-sm-none d-md-none d-lg-block d-xl-none d-xxl-none"
                id="button_toggle_right_lg"
                onclick="toggle_toolbar('right', 'lg')">
         <i class="bi bi-toggles"></i>
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm float-right m-0 p-1 d-none d-sm-none d-md-none d-lg-none d-xl-block d-xxl-none"
                id="button_toggle_right_xl"
                onclick="toggle_toolbar('right', 'xl')">
         <i class="bi bi-toggles"></i>
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm float-right m-0 p-1 d-none d-sm-none d-md-none d-lg-none d-xl-none d-xxl-block"
                id="button_toggle_right_xxl"
                onclick="toggle_toolbar('right', 'xxl')">
         <i class="bi bi-toggles"></i>
        </button>
      </div>
     </div>
    </nav>
   </div> 
  </div>
 </div>

<script type="text/javascript" src="js/faster-than-quick/bootstrap.bundle.min.js"></script>
<script type="text/javascript" src="js/faster-than-quick/x3dom.js"></script>
<script type="text/javascript" src="js/faster-than-quick/ftq.js"></script>
<script type="text/javascript" src="js/faster-than-quick/<?=$problem?>.js"></script>
<script>
var id = "<?=$id?>";
var problem = "<?=$problem?>";
function geo_ready() {
  // console.log("GEO READY!");

<?php
// TODO: php or javascript?

for ($i = 1; $i <= $cad["faces"]; $i++) {
?>
if (typeof cad__face<?=$i?> != "undefined") {
  cad__face<?=$i?>.onmouseover = function() { face_over(<?=$i?>) };
  cad__face<?=$i?>.onmouseout  = function() { face_out(<?=$i?>) };
  cad__face<?=$i?>.onclick  = function() { face_click(<?=$i?>) };
}
<?php
}

for ($i = 1; $i <= $cad["edges"]; $i++) {
?>
if (typeof cad__edge<?=$i?> != "undefined") {
  cad__edge<?=$i?>.onmouseover = function() { edge_over(<?=$i?>) };
  cad__edge<?=$i?>.onmouseout  = function() { edge_out(<?=$i?>) };
  cad__edge<?=$i?>.onclick  = function() { edge_click(<?=$i?>) };
}
<?php
}


?>
/*
  for (i = 1; i <= <?=$cad["faces"]?>; i++) {
    document.getElementById("cad__face"+i).onmouseover = function() { face_over(i) }
    document.getElementById("cad__face"+i).onmouseout  = function() { face_out(i) }
    document.getElementById("cad__face"+i).onclick  = function() { face_click(i) }
  }
*/

  init_small_axes();
<?php
  if ($has_mesh) {
?>
    update_mesh("<?=$mesh_hash?>");
    mesh_lines("hide")
    mesh_triangles("hide")
<?php
  }
?>

  change_step(<?= (($has_results)) ? 3 : (($has_mesh_valid) ? 2 : 1)?>);
}
 </script>
</body>
</html>
