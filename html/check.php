<?php

// --- data dir ----------------------------------
$data_dir = __DIR__ . "/../data";
echo "[info] data_dir is {$data_dir}<br>\n";

$username_output = [];
exec('whoami', $username_output);
$user = $username_output[0];

echo "[info] username running the web server is {$user}<br>\n";

if (file_exists($data_dir) === false) {
  if (mkdir($data_dir, 0777) === false) {
    echo "[error] cannot create data dir {$data_dir}<br>\n";
    exit(1);
  }
} else {
  if (is_dir($data_dir) === false) {
    echo "[error] data dir exists but is not a directory<br>\n";
    exit(2);
  }
}

if (is_writable($data_dir)) {
  echo "[good] {$data_dir} is writable by user {$user}<br>\n";
} else {
  echo "[error] {$data_dir} is not writable by user {$user}<br>\n";
  exit(3);
}

// --- bin dir ----------------
$bin_dir = __DIR__ . "/../bin";
echo "[info] bin_dir is {$bin_dir}<br>\n";

if (file_exists($bin_dir) && is_dir($bin_dir)) {
  echo "[good] {$bin_dir} exists<br>\n";
} else {
  echo "[error] {$bin_dir} does not exist<br>\n";
  exit(4);
}




// --- logging ----------------------------------
include(__DIR__ . "/common.php");
$username = "root";
$err = suncae_log("running check.php script");

if ($err == 0) {
  echo "[good] logging works<br>\n";
} else {
  echo "[error] cannot create a log entry<br>\n";
  exit(5);
}


// conf
include(__DIR__ . "/../conf.php");

// --- auth ----------------------------------
if (file_exists(__DIR__ . "/../auths/{$auth}/auth.php")) {
  echo "[good] auth {$auth} exists<br>\n";
} else {
  echo "[error] auth {$auth} does not exist<br>\n";
  exit(6);
}

// --- ux ----------------------------------
if (file_exists(__DIR__ . "/../uxs/{$ux}/index.php")) {
  echo "[good] ux {$ux} exists<br>\n";
} else {
  echo "[error] ux {$ux} does not exist<br>\n";
  exit(7);
}

if ($ux == "faster-than-quick") {
  foreach (['css/bootstrap.min.css', 'css/katex.min.css', 'css/x3dom.css'] as $i) {
    if (file_exists(__DIR__ . "/../uxs/{$ux}/{$i}")) {
      echo "[good] {$i} exists<br>\n";
    } else {
      echo "[error] {$i} does not exist<br>\n";
      exit(8);
    }
  }

  // pandoc
  if (file_exists("{$bin_dir}/pandoc")) {
    echo "[good] pandoc binary exists<br>\n";
    echo "[info] " . shell_exec("ls -la {$bin_dir}/pandoc") . "<br>\n";
  } else {
    echo "[error] pandoc binary does not exist<br>\n";
    exit(9);
  }
  $exec_output = [];
  exec("{$bin_dir}/pandoc --version 2>&1", $exec_output, $err);
  // TODO: check version is good enough
  if ($err == 0) {
    echo "[good] pandoc version is {$exec_output[0]}<br>\n";
  } else {
    echo "[error] pandoc binary does not work<br>\n";
    for ($i = 0; $i < count($exec_output); $i++) {
      echo "[info] {$exec_output[$i]}<br>\n";
    }
    exit(10);
  }
}


// --- cadimporter ----------------------------------
if (file_exists(__DIR__ . "/../cadimporters/{$cadimporter}/import_cad.php")) {
  echo "[good] cadimporter {$cadimporter} exists<br>\n";
} else {
  echo "[error] cadimporters {$cadimporter} does not exist<br>\n";
  exit(11);
}



// --- cadprocessor ----------------------------------
if (file_exists(__DIR__ . "/../cadprocessors/{$cadprocessor}/process.php")) {
  echo "[good] cadprocessor {$cadprocessor} exists<br>\n";
} else {
  echo "[error] cadprocessor {$cadprocessor} does not exist<br>\n";
  exit(12);
}


if ($cadprocessor == "gmsh") {
  foreach (['css/bootstrap.min.css', 'css/katex.min.css', 'css/x3dom.css'] as $i) {
    if (file_exists(__DIR__ . "/../uxs/{$ux}/{$i}")) {
      echo "[good] {$i} exists<br>\n";
    } else {
      echo "[error] {$i} does not exist<br>\n";
      exit(13);
    }
  }

  // gmsh
  if (file_exists("{$bin_dir}/gmsh")) {
    echo "[good] gmsh binary exists<br>\n";
    echo "[info] " . shell_exec("ls -la {$bin_dir}/gmsh") . "<br>\n";
  } else {
    echo "[error] gmsh binary does not exist<br>\n";
    exit(14);
  }
  $exec_output = [];
  exec("{$bin_dir}/gmsh -version 2>&1", $exec_output, $err);
  // TODO: check version is good enough
  if ($err == 0) {
    echo "[good] gmsh version is {$exec_output[0]}<br>\n";
  } else {
    echo "[error] gmsh binary does not work<br>\n";
    for ($i = 0; $i < count($exec_output); $i++) {
      echo "[info] {$exec_output[$i]}<br>\n";
    }
    exit(15);
  }
  
  // python
  $exec_output = [];
  exec("which python", $exec_output, $err);
  if ($err == 0) {
    echo "[good] python binary exists at {$exec_output[0]}<br>\n";
  } else {
    echo "[error] python binary does not exist<br>\n";
    exit(16);
  }
  $exec_output = [];
  exec("python --version 2>&1", $exec_output, $err);
  // TODO: check version is good enough
  if ($err == 0) {
    echo "[good] python version is {$exec_output[0]}<br>\n";
  } else {
    echo "[error] python binary does not work<br>\n";
    for ($i = 0; $i < count($exec_output); $i++) {
      echo "[info] {$exec_output[$i]}<br>\n";
    }
    exit(17);
  }
  
  // gmsh python wrapper
  $exec_output = [];
  exec("python " . __DIR__ . "/../cadprocessors/gmsh/gmshcheck.py 2>&1", $exec_output, $err);
  // TODO: check version is good enough
  if ($err == 0) {
    echo "[good] python gmsh wrapper version is {$exec_output[0]}<br>\n";
  } else {
    echo "[error] python gmsh wrapper does not work<br>\n";
    for ($i = 0; $i < count($exec_output); $i++) {
      echo "[info] {$exec_output[$i]}<br>\n";
    }
    exit(18);
  }
  
  
}
