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
    $contrib=$this->getMapper('contrib');
    foreach($params as $key => $param){
      $contrib->$key=$param;
    }
    $contrib->save();
    return $contrib->get('_id');
  }

  public function addTagContrib($params){
    $contrib=$this->getMapper('tag_contrib');
    foreach($params as $key => $param){
      $contrib->$key=$param;
    }
    $contrib->save();
  }

  public function getArticle($params){
    return $this->getMapper('article')->find(array('id_article=?',$params['id_article']));
  }

  public function articleInMyWishlist($params){
    return $this->getMapper('souhait')->find(array('id_article=? AND id_user=?', $params['id_article'], $params['id_user']));
  }

  public function reWishlister($params){
    $mapper=$this->getMapper('souhait');
    foreach($params as $key => $param){
      $mapper->$key=$param;
    }
    $mapper->save();
    return '1';
  }

  public function getTagDefault($params){
    return $this->getMapper('tag')->load(array('id_user=? AND user_enfant=?',$params['id_user'], 'Toutes'));
  }

  public function getContrib($params){
    return $this->dB->exec('SELECT *, c.nom AS nom_contrib FROM contrib c INNER JOIN users u ON c.user_createur=u.id_user WHERE c.clef=:clef AND c.user_referent!=:id', array('clef'=>$params['clef'], 'id'=>$params['id_user']));
  }


  public function getProductsContrib($params){
    return $this->dB->exec('SELECT DISTINCT a.* FROM tag_contrib tc INNER JOIN appartenance app ON app.id_tag=tc.id_tag INNER JOIN souhait s ON s.id_souhait=app.id_souhait INNER JOIN article a ON a.id_article=s.id_article WHERE tc.id_contrib=?', $params['id_contrib']);
  }

  public function likeArticleContrib($params){
    $map=$this->getMapper('like_souhait');
    $like=$map->load(array('id_contrib=? AND id_article=? AND id_user=?', $params['id_contrib'], $params['id_article'],$params['id_user']));
    if(!empty($like)){
      $map->id_contrib=$params['id_contrib'];
      $map->id_article=$params['id_article'];
      $map->id_user=$params['id_user'];
      $map->save();
      return true;
    }
    else{
      $like->erase();
      return false;
    }
  }

  public function getArticleContrib($params){
    return $this->dB->exec('SELECT * FROM tag_contrib tc LEFT JOIN appartenance app ON app.id_tag=tc.id_tag LEFT JOIN souhait s ON s.id_souhait=app.id_souhait WHERE tc.id_contrib=? AND s.id_article=?', $params['id_contrib'], $params['id_article']);
  }

  public function getUsersContrib($params){
    return $this->dB->exec('SELECT * FROM don d INNER JOIN users u ON d.id_user=u.id_user WHERE d.id_contrib', $params['id_contrib']);
  }

}