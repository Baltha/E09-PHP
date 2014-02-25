<?php
class Jerem_model extends App_model{
  
  
  function __construct(){
    parent::__construct();
  } 

  public function updatePicFacebook($params){
    $pic=$this->getMapper('users');
    $pic->load(array('id_user=?',$params['id_user']));
    $pic->photo=$params['pic'];
    $pic->save();
  }


  public function addContrib($params){
    $page=$this->getMapper('page');
    foreach($params as $key => $param){
      $page->$key=$param;
    }
    $page->save();

  }
}