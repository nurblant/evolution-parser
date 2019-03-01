<?php
/**
 * evolution-parser
 *
 * @category  parser
 * @version   1.0.0
 * @license     GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
 * @author nurblant
 *
 */
if (!defined('MODX_BASE_PATH')) {die('What are you doing? Get out of here!');}

include_once ($modx->config["base_path"].'assets/snippets/evolution-parser/libs/phpQuery.php');
include_once ($modx->config["base_path"].'assets/snippets/evolution-parser/libs/CakeMODx.class.php');
include_once ($modx->config["base_path"].'assets/snippets/evolution-parser/libs/functions.php');

$doc = new CakeMODx;
  $fields = array(
    'pagetitle' => 'Тестовый ресурс',
    'template' => 6,
    'parent' => 1,
    'published' => 0,
    //'link_attributes' => $link,
    //'menutitle' => $strn
  );
  $id = $doc->newDocument($fields);

  if ($id) {
    echo 'Документ создан '.$id;
    $alias = $trans->stripAlias('Тестовый ресурс', $char_restrict, $word_separator);
    // перезапись ресурса
    $fields = array(
      'alias' => $id.'-'.$alias,
      'pagetitle' => $pagetitle,
      //'content' => $outPage,
    );
    $doc->updateDocument($id,$fields);
    $doc->updateCache();
  }
    
return $output;