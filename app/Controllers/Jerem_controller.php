<?php
class Jerem_controller extends App_controller{

  public function __construct(){
    parent::__construct();
  }

  public function updatePicFacebook($f3){
    /*
      * Vérifie si l'id session existe
      * Véfifie si l'id session est identique au get id_user
      * Verifie si l'user existe dans la BDD
      * Si user existe et qu'il a un id_facebook on effectue la requete pour le lien de sa photo de profil
      * Si elle est identique à la précédente on ne fait rien sinon on la change
    */
    if($f3->exists('SESSION.id')){
      if($f3->get('SESSION.id')==$f3->get('PARAMS.id_user')){
        $user=$this->model->getUser(array('id_user'=>$f3->get('PARAMS.id_user')));
        if(!empty($user) && !empty($user['id_facebook'])){
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
      formulaire */

      /* STEP 2
      Verifie si l'user à qui on veut faire une contrib existe
      On vérifie si on est ami avec lui
      */

      if($f3->get('PARAMS.step')=='1'){
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
          $this->model->addContrib(array(
            'nom'=>$f3->get('POST.nom'),
            'description'=>$f3->get('POST.description'),
            'clef_page'=>uniqid(),
            'date_fin'=>$f3->get('POST.fin'), 
            'user_referent'=>$f3->get('PARAMS.id_user'),
            'user_createur'=>$f3->get('SESSION.id')
          ));
        }
      }
    }
    $this->tpl['sync']='addContrib.html';
  }

  public function reWhishlister($f3){


    // /!\ MANQUE INTEGRATION PUIS CODE DANS LE JS EN FONCTION DE LA CLASSE /!\ //


    /*
      If id article exist in URL
      If article exist in database
      If article doesn't exist in my wishlist
      ==> Add in my whishlist
      Else
      ==> Nothing
    */
    if(!empty($f3->get('PARAMS.id_article'))){
      $article=$this->model->getArticle(array('id_article'=>$f3->get('PARAMS.id_article')));
      if(count($article)==1){
        $exist=$this->model->articleInMyWishlist(array('id_article'=>$f3->get('PARAMS.id_article')));
        if(count($exist)==0){
          $this->model->reWhishlister(array('id_article'=>$f3->get('PARAMS.id_article'), 'id_user'=>$f3->get('SESSION.id'), 'date_souhait'=>date('Y-m-d H:i:s')));
      }
    }
    $this->tpl['async']='json/status.json';  
  }







}