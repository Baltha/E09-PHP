<?php
class Jerem_controller extends App_controller{

  public function __construct(){
    parent::__construct();
  }

  public function updatePicFacebook($f3){
    /*
      * If ID session exists
      * If id session and id_user in url are the same
      * If the user exists and id_facebook exists
      * Facebook request
      * Edit link picture if different with the previous
    */
    if($f3->exists('SESSION.id')){
      if($f3->get('SESSION.id')==$f3->get('PARAMS.id_user')){
        $user=$this->model->getUser(array('id_user'=>$f3->get('PARAMS.id_user')));
        if(count($user)==1 && !empty($user['id_facebook'])){
          require_once('api/facebook.php');
          $facebook = new Facebook(array(
            'appId'  => '479303535507941',
            'secret' => '2d568c782decb0e86bf9fefb5ec1f16e',
            'allowSignedRequest'=>false,
            'cookie'=>true
          ));

          // récupère l'id facebook
          $param = array(
            'method' => 'fql.query',
            'query' => 'SELECT pic FROM user WHERE uid='.$user['id_facebook'], 
            'callback' => ''
          );
          $response = $facebook->api($param);
          if(!empty($response)){
            $pic=$response[0]['pic'];
            if($pic!=$user['photo']){
              $this->model->updatePicFacebook(array('pic'=>$pic, 'id_user'=>$f3->get('PARAMS.id_user')));
              $f3->set('SESSION.profil_picture', $pic);
            }
          }
        }
      }
    }
    $f3->reroute("/wishlist");
  }

  public function addContrib($f3){
    self::getMyWishlist($f3);
    $user=$this->model->getUser(array('id_user'=>$f3->get('PARAMS.id_user')));
    if(!empty($user)){
      /* STEP 1 
      form with user tags */

      /* STEP 2
      Verifie si l'user à qui on veut faire une contrib existe
      On vérifie si on est ami avec lui
      */

      if($f3->get('PARAMS.step')=='1'){
        $f3->set('tags', $this->model->getUserTags(array('id_user'=>$f3->get('PARAMS.id_user'))));
        $this->tpl['sync']='addContrib.html';
      }
      elseif($f3->get('PARAMS.step')=='2'){
        $erreur = array();

        foreach($f3->get('POST') as $key => $value){
          if($f3->exists('POST.'.$key))
            $f3->clean($f3->get('POST'.$key));
          else
            array_push($erreur, 'Champ manquant : '.$key);
        }


        if(count($erreur)==0){
          $id=$this->model->addContrib(array(
            'nom'=>$f3->get('POST.nom'),
            'description'=>$f3->get('POST.description'),
            'clef'=>uniqid(),
            'date_fin'=>$f3->get('POST.fin'), 
            'user_referent'=>$f3->get('PARAMS.id_user'),
            'user_createur'=>$f3->get('SESSION.id')
          ));
          if(!empty($f3->get('POST.tag'))){
            foreach($f3->get('POST.tag') as $tag){
              $this->model->addTagContrib(array('id_contrib'=>$id, 'id_tag'=>$tag));
            }
          }
          
          
        }
      }
    }
    $this->tpl['sync']='addContrib.html';
  }

  public function viewContrib($f3){
    /*
      View Contrib Page
      If contrib exists in database
      Show contrib's products 
    */
    $contrib=$this->model->getContrib(array('clef'=>$f3->get('PARAMS.clef'), 'id_user'=>$f3->get('SESSION.id')));
    if(!empty($contrib)){
      $f3->set('allProducts', $this->model->getProductsContrib(array('id_contrib'=>$contrib[0]['id_contrib'])));
      $f3->set('contrib', $contrib);
    }
    $this->tpl['sync']='contrib.html';
  }

  public function likeArticleContrib($f3){
    /*
      VERIFY !
    */
    $exist=getArticleContrib(array('id_contrib'=>$f3->get('PARAMS.id_contrib'), 'id_article'=>$f3->get('PARAMS.id_article')));
    if(count($exist)!=0){
      $f3->set('status',$this->model->favorite(array('id_contrib'=>$f3->get('PARAMS.id_contrib'),'id_article'=>$f3->get('id_article'),'id_user'=>$f3->get('SESSION.id'))));
    }
    else
      $f3->set('status','0');
    $this->tpl['async']='json/status.json';
  }

  public function reWishlister($f3){

    /*
      If id article exists in URL
      If article exists in database
      If article doesn't exist in my wishlist
      ==> Add in my whishlist
      Else
      ==> Nothing
    */
    if(!empty($f3->get('PARAMS.id_article'))){
      $article=$this->model->getArticle(array('id_article'=>$f3->get('PARAMS.id_article')));
      if(count($article)==1){
        $exist=$this->model->articleInMyWishlist(array('id_article'=>$f3->get('PARAMS.id_article'), 'id_user'=>$f3->get('SESSION.id')));
        if(count($exist)==0){
          $id_tag=$this->model->getTagDefault(array('id_user'=>$f3->get('SESSION.id')));
          echo $id_tag['id_tag'];
          $f3->set('status', $this->model->reWishlister(array('id_article'=>$f3->get('PARAMS.id_article'), 'id_user'=>$f3->get('SESSION.id'), 'date_souhait'=>date('Y-m-d H:i:s')), $id_tag['id_tag']));
        }
         
      }
    }
    if(!$f3->exists('status'))
      $f3->set('status','0');
    $this->tpl['async']='json/status.json';  
  }



  



}