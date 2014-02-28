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
      'allowSignedRequest'=>false,
      'cookie'=>true
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
          'profil_picture'=>$f3->get('informations->fields.photo.value'),
          'ville'=>$f3->get('informations->fields.ville.value'),
          'access_token' => $facebook->getAccessToken()


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
        'ville'=>$f3->get('me.location.name'),
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
          'profil_picture'=>$auth->photo,
          'ville'=>$auth->ville,
          'access_token'=>$facebook->getAccessToken(),
          'ville'=>$auth->ville
        );

        $f3->set('SESSION',$user);
        $this->model->addDefaultTag($auth->id_user);
        $f3->reroute('/wishlist');
      }
    }

    $error=$f3->get('GET.error');
    $f3->set('error',isset($error));
    $this->tpl['sync']='main.html';

  }

  public function login($f3){
    $auth=$this->model->login(array(
      'login'=>$f3->get('POST.login'),
      'password' => $f3->get('POST.password')
    ));
    if(!$auth){
      $f3->reroute('/?error');
    }
    else{
      $user=array(
        'id'=>$auth->id_user,
        'firstname'=>$auth->prenom,
        'lastname'=>$auth->nom,
        'ville'=>$auth->ville
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
        },true,true);
      }

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
              'photo' => $f3->get($f3->get('UPLOADS').$_FILES['file']['name']),
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
    $f3->set('allProducts',$this->model->getProducts(array('id_user'=>$f3->get('SESSION.id'))));
    $productTags = array();
    foreach ($f3->get('allProducts') as $i => $product) {
      $productTags[$i] = $this->model->getProductTags(array('id_souhait'=>$product['id_souhait']));
    }
    $f3->set('productTags' , $productTags);
    $f3->set('tags',$this->model->getUserTags(array('id_user'=>$f3->get('SESSION.id'))));
    $f3->set('stats.nbfollowers', count($this->model->getfollowers(array('id_user'=>$f3->get('SESSION.id')))));
    $f3->set('stats.nbfollows', count($this->model->getfollows(array('id_user'=>$f3->get('SESSION.id')))));
    $f3->set('stats.wishs', count($this->model->getProducts(array('id_user'=>$f3->get('SESSION.id')))));
    $f3->set('page', "wishlist");
    $this->tpl['sync']='wishlist.html';
  }
  public function getUserWishlist($f3){
    $f3->set('user',$this->model->getUser(array('id_user'=>$f3->get('PARAMS.id_user'))));
    $f3->set('allProducts',$this->model->getProducts(array('id_user'=>$f3->get('PARAMS.id_user'))));
    $productTags = array();
    foreach ($f3->get('allProducts') as $i => $product) {
      $productTags[$i] = $this->model->getProductTags(array('id_souhait'=>$product['id_souhait']));
    }
    $f3->set('productTags' , $productTags);
    $f3->set('tags',$this->model->getUserTags(array('id_user'=>$f3->get('PARAMS.id_user'))));
    $f3->set('stats.nbfollowers', count($this->model->getfollowers(array('id_user'=>$f3->get('SESSION.id')))));
    $f3->set('stats.nbfollows', count($this->model->getfollows(array('id_user'=>$f3->get('SESSION.id')))));
    $f3->set('stats.wishs', count($this->model->getProducts(array('id_user'=>$f3->get('SESSION.id')))));
    $f3->set('page', "wishlist");
    $this->tpl['sync']='wishlist.html';
  }
  
  
  public function addProduct($f3){
    $product=$this->model->parseProduct(array('product'=>$f3->get('POST.product')));
    $f3->set('ESCAPE',FALSE);
    $f3->set('product',$product);
    $f3->set('SESSION.product',$product);
    if($f3->get('POST.newtag')){
      $date = date('Y-m-d H:i:s');
      $f3->set('product',$this->model->addTag(array('nom'=>$f3->get('POST.newtag'),'id_user'=>$f3->get('SESSION.id'),'date_tag'=>$date)));
      $f3->set('theTag', $f3->get('POST.newtag'));
    }
    else{
      $f3->set('theTag', $f3->get('POST.tag'));
    }
    $f3->set('product',$this->model->addProduct(array('nom'=>$f3->get('POST.nom'),'product'=>$f3->get('SESSION.product'),'tag'=>$f3->get('theTag'),'id_user'=>$f3->get('SESSION.id'))));
    $f3->set('SESSION.product',array());
    $f3->set('lastProduct',$this->model->lastProduct(array('id_user'=>$f3->get('SESSION.id'))));
    $lastProduct = $f3->get('lastProduct');
    $f3->set('productTags' , $this->model->getProductTags(array('id_souhait'=>$lastProduct[0]["id_souhait"])));
    $this->tpl['async']='partials/newItem.html';
  }

  public function deleteProduct($f3){
   $f3->set('status',$this->model->deleteProduct(array('id_souhait'=>$f3->get('PARAMS.id_souhait'))));
   $this->tpl['async']='json/status.json';
  }

  public function myFollow($f3){
    require_once('api/facebook.php');
    $facebook = new Facebook(array(
      'appId'  => '479303535507941',
      'secret' => '2d568c782decb0e86bf9fefb5ec1f16e',
      'allowSignedRequest' => false,
      'cookie' => true
   ));
    $myfollows = $this->model->getfollows(array('id_user'=>$f3->get('SESSION.id')));
    $myfollowsuser = array();
    foreach ($myfollows as $onefollow) {
      $onefollowuser = $this->model->getUser(array('id_user'=>$onefollow["fields"]["user_enfant"]["value"]));
      array_push($myfollowsuser, $onefollowuser);
    }
    $f3->set('myfollows', $myfollowsuser);

    $myfollowers = $this->model->getfollowers(array('id_user'=>$f3->get('SESSION.id')));
    $myfollowersuser = array();
    foreach ($myfollowers as $onefollower) {
      $onefolloweruser = $this->model->getUser(array('id_user'=>$onefollower["fields"]["user_parent"]["value"]));
      array_push($myfollowersuser, $onefolloweruser);
    }
    $f3->set('myfollowers', $myfollowersuser);

    $ourServiceUsers = array();
    $f3->set('friends',$facebook->api('/me/friends','GET',array('access_token'=>$f3->get('SESSION.access_token'))));
    $f3->set('allusers', $this->model->getAllUsers());
    foreach ($f3->get('friends.data') as $i => $friend) {
      foreach ($f3->get('allusers') as $user) {
        if($friend["id"] == $user["id_facebook"]){
          $friend["id_user"] = $user["id_user"];
          array_push($ourServiceUsers, $friend);
        }
      }
    }
    $f3->set('FacebookFriendsUsers', $ourServiceUsers);
    $f3->set('stats.nbfollowers', count($this->model->getfollowers(array('id_user'=>$f3->get('SESSION.id')))));
    $f3->set('stats.nbfollows', count($this->model->getfollows(array('id_user'=>$f3->get('SESSION.id')))));
    $f3->set('stats.wishs', count($this->model->getProducts(array('id_user'=>$f3->get('SESSION.id')))));
    $f3->set('page', "follow");
    $this->tpl['sync']='follow.html';
  }

  public function addFollow($f3){
      $f3->set('user',$this->model->addFollow(array('user_parent'=>$f3->get('SESSION.id'), 'user_enfant'=>$f3->get('PARAMS.id_user'))));
      $f3->reroute("/myFollow");
  } 

  public function getInfos($f3){
    $f3->set('infos',$this->model->getUser(array('id_user'=>$f3->get('SESSION.id'))));
    $this->tpl['async']='partials/updateInfosForm.html';

  }
  public function setInfos($f3){
      //tableau php puis pousser f3
      $erreur = array();

    foreach($f3->get('POST') as $key => $value){
      if($f3->exists('POST.'.$key))
        $f3->clean($f3->get('POST'.$key));
      else
        array_push($erreur, 'Champ manquant : '.$key);
    }

    if(count($erreur)==0){

      if($f3->get('mdp')==$f3->get('mdp2')){

        $params=array(
          'id_user'=>$f3->get('SESSION.id'),
          'mdp'=>$this->model->password($f3->get('POST.mdp')),
          'mail'=>$f3->get('POST.mail'),
          'adresse'=>$f3->get('POST.adresse'),
          'ville'=>$f3->get('POST.ville'),
          'photo' => $f3->get($f3->get('UPLOADS').$_FILES['file']['name']),
          'code_postal'=>$f3->get('POST.cp')
        );

        if($_FILES['file']['error'] == 0){
          $photo = \Web::instance()->receive(function($file){
            $f3 = \Base::instance();
            $params['photo']=$f3->get($f3->get('UPLOADS').$_FILES['file']['name']);
          },true,true);
        }

        $this->model->setInfos($params);

        $auth=$this->model->getUserInfoAfterSignin(array(
          'mail'=>$f3->get('POST.mail')
        ));
        $f3->set('SESSION.ville',$auth->ville);
        $f3->reroute("/wishlist");
      }
    }
  } 


    public function giveIframe($f3)
    {
       $f3->set('url',$f3->get('GET.url'));
       header_remove("X-Frame-Options");
       $this->tpl['sync']='iframe.html';
    }
    public function verifIdInFrame($f3)
    {
      $id=$f3->get('SESSION.id');
      if(isset($id))
      {
        //L'utilisateur est déja connecté, appelle d'une méthod pour ajout à la wishlist du user en question.
        //et on affiche une page html annoncant que le produit a bien été ajouté.
        $product=$this->model->parseProduct(array('product'=>$f3->get('GET.url')));
        $f3->set('product',$this->model->addProduct(array(
          'nom'=>$product['nom'],
          'product'=>$product,
          'id_user'=>$id
        )));
        $this->tpl['sync']='souhaitDone.html';

      }
      else
      {
        $error=$f3->get('GET.error');
        $f3->set('error',isset($error));
        //sinon on redirige vers un formulaire de connexion, qui créera l'id user de session et ensuite sur la fonction log in frame
        $f3->set('url',$f3->get('GET.url'));
        $this->tpl['sync']='formulairelog.html';
      }
      
    }    

    public function loginInFrame($f3)
    {
      
       $auth=$this->model->login(array('login'=>$f3->get('POST.login'),'password' => $f3->get('POST.password')));
       $url=$f3->set('url',$f3->get('GET.url'));
      if(!$auth){
         $f3->reroute('/verifIdInFrame?error&url='.urlencode($url));
      }
      else{
        $user=array(
          'id'=>$auth->id_user,
          'firstname'=>$auth->prenom,
          'lastname'=>$auth->nom,
          'ville'=>$auth->ville
        );
        $f3->set('SESSION',$user);
        $f3->reroute('/verifIdInFrame?url='.urlencode($url));
      }
   }
    public function paypal($f3){
      $this->tpl['sync']='paypal.html';
    }
    public function ipn($f3){
      $f3->reroute("/wishlist");
      echo "coucou" ;
    }

    public function HandAdd($f3)
    {
      $this->tpl['async']='partials/HandForm.html';
    }

    public function ProductHand($f3)
    {
      $product=array('nom'=>$f3->get('POST.nom'),'price'=>$f3->get('POST.price'),'describe'=>$f3->get('POST.describe'),'picture'=>$f3->get('POST.picture'),'link'=>$f3->get('POST.link'),'qid'=>$f3->get('POST.qid'));
      $f3->set('product',$this->model->addProduct(array('nom'=>$f3->get('POST.nom'),'product'=>$product,'tag'=>$f3->get('POST.theTag'),'id_user'=>$f3->get('SESSION.id'))));
      $f3->reroute("/wishlist");
    }
   

}








?>