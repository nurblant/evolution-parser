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

$urlAutor = 'http://zonanot.ru/notespiano/classika/40-classical/a/97-adan';
$template = 4;
$parent = 10;
$publiched = 0;

// curl запрос
$ch = curl_init(); 
curl_setopt($ch, CURLOPT_URL, trim($urlAutor)); 
curl_setopt($ch, CURLOPT_HEADER, false); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); 
$autorPage = curl_exec($ch); 
curl_close($ch);


// парсинг
$autorDocument = phpQuery::newDocument($autorPage);
$a_urls = $autorDocument->find('div.attacments a.mod-articles-category-title');
foreach($a_urls as $a_url) {
  $pqaurl = pq($a_url);
  echo ($pqaurl->attr('href')).'<br>';
}
return 'Ok';

$doc = new CakeMODx;
  $fields = array(
    'pagetitle' => 'Тестовый ресурс',
    'template' => $template,
    'parent' => $parent,
    'published' => $publiched,
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
      //'pagetitle' => $pagetitle,
      'content' => 'Тестовое содержимое ресурса',
    );
    $doc->updateDocument($id,$fields);
    $doc->setTV(5, $id, 'Link');
    $doc->updateCache();
  }
    
return $output;