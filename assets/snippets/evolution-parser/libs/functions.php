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

function saveMedia($url, $alias, $id, $media_dir) {
    $pi = pathinfo($url); 
    $ext = $pi['extension']; 
    $name = $pi['filename']; 
  
    $ch = curl_init(); 
  
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_HEADER, false); 
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true); 
    curl_setopt($ch, CURLOPT_AUTOREFERER, true); 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
  
    $opt = curl_exec($ch); 
  
    curl_close($ch);
    
    mkdir($media_dir.$id, 0700);
    $saveFile = $media_dir.$id.'/'.$id.'-'.$alias.'.'.$ext; 
    /*if(preg_match("/[^0-9a-z\.\_\-]/i", $saveFile)) 
      $saveFile = md5(microtime(true)).'.'.$ext; */
  
    $handle = fopen($modx->config["base_path"].$saveFile, 'wb'); 
    fwrite($handle, $opt); 
    fclose($handle);
    //return $saveFile;
    
    return $saveFile;
  }