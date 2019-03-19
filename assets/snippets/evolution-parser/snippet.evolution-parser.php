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

$autorsList = 'https://instrukciya-primeneniyu.com/podbor-preparatov';
$startY = 0;
$limitY = 1;
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
$a_listUrls = $autorsListDocument->find('li.menu-item a');
echo $a_listUrls;
$iY = 0;
// foreach($a_listUrls as $a_urlAutor) {
  
//   if($iY>=$startY && $iY<($startY+$limitY)) {
//     $domain = 'https://instrukciya-primeneniyu.com';
//     $pqaurl_autor = pq($a_urlAutor);
//     $urlAutor = $domain.$pqaurl_autor->attr('href');
//     echo $urlAutor;
//     $template = 4;
//     $parent = 1;
//     $publiched = 1;
//     //$media_dir = 'assets/files/';

//     // curl запрос
//     $ch = curl_init(); 
//     curl_setopt($ch, CURLOPT_URL, trim($urlAutor)); 
//     curl_setopt($ch, CURLOPT_HEADER, false); 
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
//     curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); 
//     $autorPage = curl_exec($ch); 
//     curl_close($ch);

//     // парсинг
//     $autorDocument = phpQuery::newDocument($autorPage);
//     $a_urls = $autorDocument->find('.entry-title a');

//     foreach($a_urls as $a_url) {

//       $pqaurl = pq($a_url);
//       echo $domain.($pqaurl->attr('href')).'<br>';

//       // curl запрос
//       $ch = curl_init(); 
//       curl_setopt($ch, CURLOPT_URL, trim($domain.($pqaurl->attr('href')))); 
//       curl_setopt($ch, CURLOPT_HEADER, false); 
//       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
//       curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); 
//       $notePage = curl_exec($ch); 
//       curl_close($ch);
//       $noteDocument = phpQuery::newDocument($notePage);
//       $pageTitle = $noteDocument->find('.entry-title a')->text();

//       //$downloadUrl = $noteDocument->find('#noti a')->attr('href');
//       //$autorName = $noteDocument->find('.breadcrumbs a:last')->text();
      
//         $doc = new CakeMODx;
//         $fields = array(
//           'pagetitle' => $pageTitle,
//           'template' => $template,
//           'parent' => $parent,
//           'published' => $publiched,
//           //'link_attributes' => $link,
//           //'menutitle' => $strn
//         );
//         $id = $doc->newDocument($fields);
//     } 
//   }
//   $iY = $iY+1;
  
// }
    
return $output;