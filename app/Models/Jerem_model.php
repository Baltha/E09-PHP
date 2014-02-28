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

  public function getArticle($params){
    return $this->getMapper('article')->find(array('id_article=?',$params['id_article']));
  }

  public function articleInMyWishlist($params){
    return $this->getMapper('souhait')->find(array('id_article=? & id_user=?', $params['id_article'], $params['id_user']));
  }

  public function reWhishlister($params){
    $mapper=$this->getMapper('souhait');
    foreach($params as $key => $param){
      $mapper->$key=$param;
    }
    $mapper->save();
    return '1';
  }
}