<?php

if (file_exists("original.json") === false) {
  exec("../../../../cadimporters/upload/cadcheck.py", $output, $error_level);
  
  // TODO: keep output
  if ($error_level != 0) {
    $response["status"] = "error";
    $response["show_preview"] = false;
    if ($error_level == 1) {
      $response["error"] = "Invalid STEP file.";
    } else if ($error_level == 2) {
      $response["error"] = "Invalid CAD file.";
    } else {
      $response["error"] = "Unknown error {$error_level} when checking CAD.";
    }
    for ($i = 0; $i < count($output); $i++) {
      $response["error"] .= $output[$i];
    }
    suncae_log("CAD {$response["cad_hash"]} upload {$response["status"]} {$response["error"]}");
    return_back_json($response);
  }
}

if (file_exists("original.json")) {
  $original = json_decode(file_get_contents("original.json"), true);
  if ($original != null) {
    if ($original["solids"] == 0) {
      $response["status"] = "error";
      $response["error"] = "No solid found in CAD file.";
    } else if ($original["solids"] > 1) {
      $response["status"] = "error";
      $response["error"] = "CAD file has {$original["solids"]} solids and this PoC works with single-solid CADs only.";
    }
  } else {
    $response["status"] = "error";
    $response["show_preview"] = false;
    $response["error"] = "Cannot decode original json.";
  }
      
} else {
  $response["status"] = "error";
  $response["show_preview"] = false;
  $response["error"] = "Cannot create original json.";
}

suncae_log("CAD {$response["cad_hash"]} upload {$response["status"]} {$response["error"]}");

?>
