color["bc_1"]  = [0.60, 0.30, 0.90];
color["bc_2"]  = [1.00, 0.33, 0.33];
color["bc_3"]  = [0.67, 0.77, 0.37];
color["bc_4"]  = [0.16, 0.83, 1.00];
color["bc_5"]  = [0.40, 1.00, 0.40];
color["bc_6"]  = [1.00, 0.90, 0.50];
color["bc_7"]  = [0.40, 0.80, 0.85];
color["bc_8"]  = [1.00, 0.00, 0.40];
color["bc_9"]  = [0.16, 0.83, 0.00];
color["bc_10"] = [1.00, 0.40, 0.00];

function bc_hide_all(i) {
  bootstrap_hide("bc_value_"+i+"_custom");
  bootstrap_hide("bc_value_"+i+"_temperature");
  bootstrap_hide("bc_value_"+i+"_heatflux");
  bootstrap_hide("bc_value_"+i+"_convection");
}
