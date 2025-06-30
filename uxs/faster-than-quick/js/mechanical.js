color["bc_1"] = [0.82, 0.22, 0.68];
color["bc_2"] = [0.37, 0.85, 0.16];
color["bc_3"] = [0.93, 0.91, 0.26];
color["bc_4"] = [0.33, 0.86, 1.00];
color["bc_5"] = [1.00, 0.66, 0.66];
color["bc_6"] = [1.00, 0.50, 0.50];
color["bc_7"] = [0.00, 0.80, 1.00];
color["bc_8"] = [0.55, 0.37, 0.82];
color["bc_9"] = [0.00, 1.00, 0.80];
color["bc_10"] = [1.00, 0.40, 0.00];

color["bc_10"] = [0.10, 0.25, 0.50];
color["bc_11"] = [1.00, 0.80, 0.16];
color["bc_12"] = [0.75, 0.15, 0.90];
color["bc_13"] = [0.55, 0.01, 0.22];
color["bc_14"] = [0.17, 0.38, 0.01];
color["bc_15"] = [0.78, 0.44, 0.21];
color["bc_16"] = [1.00, 0.25, 0.50];
color["bc_17"] = [0.25, 0.83, 0.60];
color["bc_18"] = [0.35, 0.75, 0.60];
color["bc_19"] = [0.21, 0.78, 0.78];

function bc_hide_all(i) {
  bootstrap_hide("bc_value_"+i+"_custom");
  bootstrap_hide("bc_value_"+i+"_fixture");
  bootstrap_hide("bc_value_"+i+"_pressure");
  bootstrap_hide("bc_value_"+i+"_force");
}

function bc_fixture_update(i) {
  string = "";
  if (document.getElementById("bc_"+i+"_fixture_u").checked) {
    string += "u=0 ";
  }
  if (document.getElementById("bc_"+i+"_fixture_v").checked) {
    string += "v=0 ";
  }
  if (document.getElementById("bc_"+i+"_fixture_w").checked) {
    string += "w=0 ";
  }
  
  ajax2problem("bc_"+i+"_value" , string);
}
