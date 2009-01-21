<?php 
/**
* @author Guillermo Vargas, http://joomla.vargas.co.cr
* @email guille@vargas.co.cr
* @package Xmap
* @license GNU/GPL
* @description Xmap plugin for Gallery2 Brige component
*/

defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

class xmap_com_jfusion
{
   /*
   * This function is called before a menu item is printed. We use it to set the
   * proper uniqueid for the item
   */
   function prepareMenuItem(&$node) {
       $menu =& JSite::getMenu();
       $g2params = $menu->getParams($node->id);
   }

   function getTree( &$xmap, &$parent, $params ) {
      $jFusionPath = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php';
      if ( !file_exists($jFusionPath) ) {
          return false;
      }
      require_once( $jFusionPath );
      $menu =& JSite::getMenu();
      $jFusionParam = $menu->getParams($parent->id);

      $jPluginParam = new JParameter('');
      $jPluginParam->loadArray(unserialize(base64_decode($jFusionParam->get('JFusionPluginParam'))));
      $jname = $jPluginParam->get('jfusionplugin');

      $jFusionAdmin = JFusionFactory::getAdmin($jname);
      if(!method_exists($jFusionAdmin,"getSitemapTree")) {
         return false;
      }
      
      //  init params
      $include_items = xmap_com_jfusion::getParam($params,'include_items',1);
      $include_items = ( $include_items == 1
                                  || ( $include_items == 2 && $xmap->view == 'xml')
                                  || ( $include_items == 3 && $xmap->view == 'html'));
      $params['include_items'] = $include_items;

      $priority = xmap_com_jfusion::getParam($params,'cat_priority',$parent->priority);
      $changefreq = xmap_com_jfusion::getParam($params,'cat_changefreq',$parent->changefreq);
      if ($priority  == '-1')
          $priority = $parent->priority;
      if ($changefreq  == '-1')
          $changefreq = $parent->changefreq;

      $params['cat_priority'] = $priority;
      $params['cat_changefreq'] = $changefreq;

      $priority = xmap_com_jfusion::getParam($params,'item_priority',$parent->priority);
      $changefreq = xmap_com_jfusion::getParam($params,'item_changefreq',$parent->changefreq);
      if ($priority  == '-1')
          $priority = $parent->priority;
      if ($changefreq  == '-1')
          $changefreq = $parent->changefreq;

      $params['item_priority'] = $priority;
      $params['item_changefreq'] = $changefreq;

      $tree = $jFusionAdmin->getSitemapTree($jFusionParam, $jPluginParam, $parent->id);

      xmap_com_jfusion::printTree( $xmap,$tree,$params,$rootItems );
   }


   function printTree( &$xmap,&$tree,$params,&$items )
   {
      if( !$tree )
         return null;

      $xmap->changeLevel(1);
      $media = array();
      foreach( $tree as $item ) {

         // If it is an album
         if ( $item->element == "group" ) {
            $item->priority = $params['cat_priority'];
            $item->changefreq = $params['cat_changefreq'];

            if ($xmap->printNode($item) !== false) {
                xmap_com_jfusion::printTree( $xmap,$item->tree,$params,$childIds );
            }
         } elseif ($params['include_items'] && $item->element == "element") {
            $item->priority = $params['item_priority'];
            $item->changefreq = $params['item_changefreq'];
            $media[] = $item;
         }
      }

      foreach ($media as $pic ) {
          $xmap->printNode($pic);
      }
      $xmap->changeLevel(-1);

   }

   function getParam($arr, $name, $def) {
        return JArrayHelper::getValue( $arr, $name, $def, '' );
   }
}
