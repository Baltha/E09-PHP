<?php
class App_controller extends Controller{

  public function __construct(){
    parent::__construct();
  }

  public function home($f3){
    
    require_once('api/facebook.php');
    $facebook = new Facebook(array(
      'appId'  => '479303535507941',
      'secret' => '2d568c782decb0e86bf9fefb5ec1f16e',
    ));

    // récupère l'id facebook
    $f3->set('user', $facebook->getUser());

    if ($f3->get('user')) {

      // vérification si l'id facebook est déjà dans la base
      $f3->set('informations', $this->model->getUserFb(array('id_facebook'=>$f3->get('user'))));
      if($f3->get('informations')==false){
        // si pas d'informations on va chercher avec l'api les données
        try {
          // me : informations personnelles
          // friends : liste des amis du user
          $f3->set('me', $facebook->api('/me'));
          $f3->set('friends', $facebook->api('/me/friends'));
        } 
        catch (FacebookApiException $e) {
          error_log($e);
          $f3->set('user', null);
        }

      }else{
        $user=array(
          'id'=>$f3->get('informations->fields.id_user.value'),
          'firstname'=>$f3->get('informations->fields.prenom.value'),
          'lastname'=>$f3->get('informations->fields.nom.value'),
          'profil_picture'=>$f3->get('informations->fields.photo.value')
        );
        // On lance la session, on configure le lien de lougout et redirige vers wishlist
        $f3->set('SESSION',$user); 
        $f3->reroute('/wishlist');
        
      }
    }else{
       $f3->set('statusUrl', $facebook->getLoginStatusUrl());
       $f3->set('loginUrl', $facebook->getLoginUrl(array('scope' => 'email, user_birthday')));
     }

 
    if($f3->exists('me')){
      // récupération et mise en variable des informations
      $param = array(
        'method' => 'fql.query',
        'query' => 'SELECT pic FROM user WHERE uid='.$f3->get('user'),
        'callback' => ''
      );
      $response = $facebook->api($param);
      $f3->set('me.pic', $response[0]['pic']);


      // envoie en BDD des informations ciblées
      $this->model->addUser(array(
        'nom'=>$f3->get('me.last_name'), 
        'prenom'=>$f3->get('me.first_name'),
        'naissance'=>date('Y-m-d', strtotime($f3->get('me.birthday'))),
        'mail'=>$f3->get('me.email'), 
        'sexe'=>$f3->get('me.gender'),
        'photo'=>$f3->get('me.pic'),
        'id_facebook'=>$f3->get('user')));
      $auth=$this->model->getUserInfoAfterSignin(array(
          'mail'=>$f3->get('me.email')
      ));

      if(!$auth){
        $f3->set('error', 'Oops , vos identifians sont érronés. Veuillez réessayer');
        $this->tpl['sync']='login.html';
      }
      else{
        $user=array(
          'id'=>$auth->id_user,
          'firstname'=>$auth->prenom,
          'lastname'=>$auth->nom,
          'profil_picture'=>$auth->photo
        );

        $f3->set('SESSION',$user);
        $this->model->addDefaultTag($auth->id_user);
        $f3->reroute('/wishlist');
      }
    }



    $this->tpl['sync']='main.html';

  }

  public function login($f3){
    $auth=$this->model->login(array(
      'login'=>$f3->get('POST.login'),
      'password' => $f3->get('POST.password')
    ));
    if(!$auth){
      $f3->set('error', 'Oops , vos identifians sont érronés. Veuillez réessayer');
      $this->tpl['sync']='login.html';
    }
    else{
      $user=array(
        'id'=>$auth->id_user,
        'firstname'=>$auth->prenom,
        'lastname'=>$auth->nom
      );
      $f3->set('SESSION',$user);
      $f3->reroute('/wishlist');
    }  
  }

  public function logout($f3){
    $f3->clear('SESSION');
    $f3->reroute('/');
  }

  public function inscription($f3){


      //tableau php puis pousser f3
      $erreur = array();

      foreach($f3->get('POST') as $key => $value){
        if($f3->exists('POST.'.$key))
          $f3->clean($f3->get('POST'.$key));
        else
          array_push($erreur, 'Champ manquant : '.$key);
      }

      // $f3->set('file', \Web::instance()->receive(function($file){
      //       print_r($file["name"]);
      //  },true,true));

      if($_FILES['file']['error'] == 0){
        $photo = \Web::instance()->receive(function($file){
            $f3 = \Base::instance();
            $f3->set('photo_url', $f3->get('UPLOADS').$_FILES['file']['name']);
        },true,true);
      }
      print_r($f3->get('photo_url'));

      if(count($erreur)==0){

        if($f3->get('mdp')==$f3->get('mdp2')){
             // pas d'erreur on envoie
          // d'abord vérif si l'adresse mail est déjà présente dans la BDD dans ce cas on l'indique
          if($this->model->verifNewUser(array('mail'=>$f3->get('mail')))==false){
            $this->model->addUser(array(
              'nom'=>$f3->get('POST.nom'),
              'prenom'=>$f3->get('POST.prenom'),
              'mdp'=>$this->model->password($f3->get('POST.mdp')),
              'naissance'=>$f3->get('POST.naissance'),
              'mail'=>$f3->get('POST.mail'),
              'sexe'=>$f3->get('POST.sexe'),
              'photo' => $f3->get('POST.photo_url'),
              'adresse'=>$f3->get('POST.adresse'),
              'ville'=>$f3->get('POST.ville'),
              'code_postal'=>$f3->get('POST.cp')
            ));

            echo $f3->get('POST.mail');
            $auth=$this->model->getUserInfoAfterSignin(array(
              'mail'=>$f3->get('POST.mail')
            ));

            $user=array(
              'id'=>$auth->id_user,
              'firstname'=>$auth->prenom,
              'lastname'=>$auth->nom,
              'ville'=>$auth->ville,
              'profil_picture'=>$auth->photo
            );
            $f3->set('SESSION',$user);
            $this->model->addDefaultTag($auth->id_user);
            $f3->reroute("/wishlist");
          }
          else{
            array_push($erreur, 'Adresse mail déjà présente');
            $f3->set('erreur', $erreur);
          }  
        }
        else{
          array_push($erreur, 'Les mots de passe ne sont pas identiques');
          $f3->set('erreur', $erreur);
        }
      }
      else
        $f3->set('erreur', $erreur);
  }

  public function getMyWishlist($f3){
    $this->tpl['sync']='wishlist.html';
    $f3->set('allProducts',$this->model->getProducts(array('id_user'=>$f3->get('SESSION.id'))));
    $productTags = array();
    foreach ($f3->get('allProducts') as $i => $product) {
      $productTags[$i] = $this->model->getProductTags(array('id_souhait'=>$product['id_souhait']));
    }
    $f3->set('productTags' , $productTags);
    $f3->set('tags',$this->model->getUserTags(array('id_user'=>$f3->get('SESSION.id'))));
  }
  public function getUserWishlist($f3){
          $f3->set('user',$this->model->getUser(array('id_user'=>$f3->get('PARAMS.id_user'))));
          $this->tpl['sync']='wishlist.html';  
  }
  
  
  public function addProduct($f3){
    $product=$this->model->parseProduct(array('product'=>$f3->get('POST.product')));
    $f3->set('ESCAPE',FALSE);
    $f3->set('product',$product);
    $f3->set('SESSION.product',$product);
    $f3->set('product',$this->model->addProduct(array('nom'=>$f3->get('POST.nom'),'product'=>$f3->get('SESSION.product'),'tag'=>$f3->get('POST.tag'),'id_user'=>$f3->get('SESSION.id'))));
    $f3->set('SESSION.product',array());
    $f3->set('lastProduct',$this->model->lastProduct(array('id_user'=>$f3->get('SESSION.id'))));
    $lastProduct = $f3->get('lastProduct');
    $f3->set('productTags' , $this->model->getProductTags(array('id_souhait'=>$lastProduct[0]["id_souhait"])));
    $this->tpl['async']='partials/newItem.html';
  }

  public function deleteProduct($f3){

   $f3->set('OneProduct',$this->model->deleteProduct(array('id_souhait'=>$f3->get('PARAMS.id_souhait'))));   
   $f3->set('status',$this->model->deleteProduct(array('id_souhait'=>$f3->get('PARAMS.id_souhait'))));
   $this->tpl['async']='json/status.json';
  }

  public function newTag($f3){
    $date = date('Y-m-d H:i:s');
    $f3->set('product',$this->model->addTag(array('nom'=>$f3->get('POST.tag'),'id_user'=>$f3->get('SESSION.id'),'date_tag'=>$date)));
    $f3->reroute("/wishlist");
  }


  // public function searchUsers($f3){
  //   $f3->set('users',$this->model->searchUsers(array('keywords'=>$f3->get('POST.name'),'filter'=>$f3->get('POST.filter'))));
  //   $this->tpl['async']='partials/users.html';
  // }
  
  // public function favorite($f3){
  //      $f3->set('status',$this->model->favorite(array('favId'=>$f3->get('PARAMS.favId'),'logId'=>$f3->get('logId'))));
  //     $this->tpl['async']='json/status.json';
  // }


}








?>