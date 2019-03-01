<?php 
//if (!isset ($alias)) { return ; }
if (!isset ($plugin_dir) ) { $plugin_dir = 'transalias'; }
if (!isset ($plugin_path) ) { $plugin_path = $modx->config['base_path'].'assets/plugins/'.$plugin_dir; }
if (!isset ($table_name)) { $table_name = 'russian'; }
if (!isset ($char_restrict)) { $char_restrict = 'lowercase alphanumeric'; }
if (!isset ($remove_periods)) { $remove_periods = 'No'; }
if (!isset ($word_separator)) { $word_separator = 'dash'; }
if (!isset ($override_tv)) { $override_tv = ''; }
if (!class_exists('TransAlias')) {
    require_once $plugin_path.'/transalias.class.php';
}
$trans = new TransAlias($modx);

if(!empty($override_tv)) {
    $tvval = $trans->getTVValue($override_tv);
    if(!empty($tvval)) {
        $table_name = $tvval;
    }
}

$trans->loadTable($table_name, $remove_periods);