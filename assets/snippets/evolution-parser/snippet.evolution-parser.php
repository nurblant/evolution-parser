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

$autorsList = 'http://zonanot.ru/notespiano/classika';
$startY = 21;
$limitY = 30;
// curl запрос
$ch = curl_init(); 
curl_setopt($ch, CURLOPT_URL, trim($autorsList)); 
curl_setopt($ch, CURLOPT_HEADER, false); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); 
$autorsListPage = curl_exec($ch); 
curl_close($ch);

// парсинг
$autorsListDocument = phpQuery::newDocument($autorsListPage);
$a_listUrls = $autorsListDocument->find('div.attacments a');
echo $a_listUrls;
$iY = 0;
foreach($a_listUrls as $a_urlAutor) {
  
  if($iY>=$startY && $iY<($startY+$limitY)) {
    $domain = 'http://zonanot.ru';
    $pqaurl_autor = pq($a_urlAutor);
    $urlAutor = $domain.$pqaurl_autor->attr('href');
    echo $urlAutor;
    $template = 6;
    $parent = 1;
    $publiched = 1;
    $media_dir = 'assets/files/';

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
      echo $domain.($pqaurl->attr('href')).'<br>';

      // curl запрос
      $ch = curl_init(); 
      curl_setopt($ch, CURLOPT_URL, trim($domain.($pqaurl->attr('href')))); 
      curl_setopt($ch, CURLOPT_HEADER, false); 
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); 
      $notePage = curl_exec($ch); 
      curl_close($ch);
      $noteDocument = phpQuery::newDocument($notePage);
      $pageTitle = $noteDocument->find('.page-header h1')->text();

      $downloadUrl = $noteDocument->find('#noti a')->attr('href');
      $autorName = $noteDocument->find('.breadcrumbs a:last')->text();
      
        $doc = new CakeMODx;
        $fields = array(
          'pagetitle' => $pageTitle,
          'template' => $template,
          'parent' => $parent,
          'published' => $publiched,
          //'link_attributes' => $link,
          //'menutitle' => $strn
        );
        $id = $doc->newDocument($fields);

        if ($id) {
          echo 'Документ создан '.$id;
          $alias = $trans->stripAlias($pageTitle, $char_restrict, $word_separator);
          // перезапись ресурса
          $fields = array(
            'alias' => $id.'-'.$alias,
            //'pagetitle' => $pagetitle,
            'content' => $pageTitle.' - ноты для фортепиано автора '.$autorName.' ',
          );
          $doc->updateDocument($id,$fields);
          //echo $domain.$downloadUrl;
          $doc->setTV(6, $id, $autorName);
          if(substr($downloadUrl, -3, 3) == 'pdf') {
            $doc->setTV(7, $id, saveMedia($domain.$downloadUrl, $alias, $id, $media_dir));
          }
          if(substr($downloadUrl, -3, 3) == 'doc') {
            $doc->setTV(13, $id, saveMedia($domain.$downloadUrl, $alias, $id, $media_dir));
          } 
          if(substr($downloadUrl, -3, 3) == 'zip') {
            $doc->setTV(8, $id, saveMedia($domain.$downloadUrl, $alias, $id, $media_dir));
          } 
          if(substr($downloadUrl, -3, 3) == 'rar') {
            $doc->setTV(8, $id, saveMedia($domain.$downloadUrl, $alias, $id, $media_dir));
          } 
          if(substr($downloadUrl, -4, 4) == 'jpeg') {
            $doc->setTV(5, $id, saveMedia($domain.$downloadUrl, $alias, $id, $media_dir));
          } 
          if(substr($downloadUrl, -4, 4) == 'JPEG') {
            $doc->setTV(5, $id, saveMedia($domain.$downloadUrl, $alias, $id, $media_dir));
          } 
          if(substr($downloadUrl, -3, 3) == 'png') {
            $doc->setTV(5, $id, saveMedia($domain.$downloadUrl, $alias, $id, $media_dir));
          } 
          if(substr($downloadUrl, -3, 3) == 'jpg') {
            $doc->setTV(5, $id, saveMedia($domain.$downloadUrl, $alias, $id, $media_dir));
          } 
          $doc->updateCache();
        }
      
    } 
  }
  $iY = $iY+1;
  
}
    
return $output;