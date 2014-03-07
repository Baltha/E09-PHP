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
          'id_facebook'=>$f3->get('user')
        );

        $f3->set('SESSION',$user);
        $this->model->addDefaultTag($auth->id_user);
        $f3->reroute('/wishlist');
      }
    }

    $error=$f3->get('GET.error');
    $f3->set('error',isset($error));
    $this->tpl['sync']='main.html';
    $f3->set('page',"home");

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

      $f3->set('file', \Web::instance()->receive(function($file){
       },true,true));


      $filename = array_keys($f3->get('file'));

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
              'photo' => $filename[0],
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
            $f3->reroute('/wishlist');
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
        $f3->reroute('/');
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
    $f3->set('followingUser', $this->model->getfollowing(array('user_parent'=>$f3->get('SESSION.id'), 'user_enfant'=>$f3->get('PARAMS.id_user'))));
    $f3->set('page', "follow");

    $this->tpl['sync']='wishlist.html';
  }
  
  
  public function addProduct($f3){
    try
    {
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
    catch(Exception $e)
    {
      $this->tpl['async']='partials/error.html';
    }
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
        if($friend["id"] == $user["id_facebook"] && !$this->model->getfollowing(array('user_parent'=>$f3->get('SESSION.id'), 'user_enfant'=>$user["id_user"]))){
          $friend["id_user"] = $user["id_user"];
          $getUser = $this->model->getUser(array('id_user'=>$user["id_user"]));
          $friend["photo"] = $getUser["fields"]["photo"]["value"];
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

  public function searchUser($f3){
    $f3->set('userSearch',$this->model->searchUser(array('keywords'=>$f3->get('POST.name'))));
    $this->tpl['async']='partials/users.html';
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

    public function HandAdd($f3)
    {
      $f3->set('tags',$this->model->getUserTags(array('id_user'=>$f3->get('SESSION.id'))));
      $this->tpl['async']='partials/HandForm.html';
    }
    public function callForm($f3)
    {
      $this->tpl['async']='partials/inscription.html'; 
    }

    public function ProductHand($f3)
    {
      $product=array('nom'=>$f3->get('POST.nom'),'price'=>$f3->get('POST.price'),'describe'=>'','picture'=>$f3->get('POST.picture'),'link'=>$f3->get('POST.link'),'qid'=>printf("%u",crc32(uniqid().mt_rand())));
      $f3->set('product',$this->model->addProduct(array('nom'=>$f3->get('POST.nom'),'product'=>$product,'tag'=>$f3->get('POST.tag'),'id_user'=>$f3->get('SESSION.id'))));
      $f3->reroute("/wishlist");
    }

    public function myContributions($f3){
        $f3->set('myContributions', $this->model->getMycContribution(array('id_user'=>$f3->get('SESSION.id'))));
        $users = array();
        $dates = array();
        foreach ($f3->get('myContributions') as $contrib) {
            $newdate = str_replace("-", "/", $contrib["date_fin"]);
            array_push($dates, $newdate);
            array_push($users, $this->model->getUser(array('id_user'=>$contrib['user_referent'])));
        }
        $f3->set('contribUsers', $users);
        $f3->set('dates', $dates);
        $this->tpl['sync']='myContributions.html';
        $f3->set('stats.nbfollowers', count($this->model->getfollowers(array('id_user'=>$f3->get('SESSION.id')))));
        $f3->set('stats.nbfollows', count($this->model->getfollows(array('id_user'=>$f3->get('SESSION.id')))));
        $f3->set('stats.wishs', count($this->model->getProducts(array('id_user'=>$f3->get('SESSION.id')))));
        $f3->set('page', 'contribution');
    }

    public function ipn($f3){

    $result_event = $_POST['custom'];
    list($id_contrib, $id_user) =  explode('|', $result_event);
    $montant = $_POST['mc_gross'];
    $date = date('Y-m-d H:i:s');
    $this->model->addDon(array('id_contrib'=>$id_contrib, 'id_user'=>$id_user, 'prix'=>$montant, 'date_don'=>$date));


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
          $user=$this->model->getUser(array('id_user'=>$f3->get('SESSION.id')));
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
      $f3->reroute("/wishlist");
    }

    public function addContrib($f3){
      $user=$this->model->getUser(array('id_user'=>$f3->get('PARAMS.id_user')));
      if(count($user)>0){
        /* STEP 1 
        form with user tags */

        /* STEP 2
        Verifie si l'user à qui on veut faire une contrib existe
        On vérifie si on est ami avec lui
        */

        if($f3->get('PARAMS.step')=='1'){
          $f3->set('list_contrib',$this->model->getContribUser(array('user_referent'=>$f3->get('PARAMS.id_user'))));
          $f3->set('SESSION.id_user', $f3->get('PARAMS.id_user'));
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
            $clef=uniqid();
            $id=$this->model->addContrib(array(
              'nom'=>$f3->get('POST.nom'),
              'description'=>$f3->get('POST.description'),
              'clef'=>$clef,
              'date_fin'=>$f3->get('POST.fin'), 
              'user_referent'=>$f3->get('PARAMS.id_user'),
              'user_createur'=>$f3->get('SESSION.id')
            ));
            $f3->set('clef', $clef);
            if(count($f3->get('POST.tag')) > 0){
              foreach($f3->get('POST.tag') as $tag){
                $this->model->addTagContrib(array('id_contrib'=>$id, 'id_tag'=>$tag));
              }
            }
            
            $f3->reroute('/contrib/'.$clef.'');
            
          }
        }
      }
      $f3->set('page', "follow");
      $f3->set('stats.nbfollowers', count($this->model->getfollowers(array('id_user'=>$f3->get('SESSION.id')))));
      $f3->set('stats.nbfollows', count($this->model->getfollows(array('id_user'=>$f3->get('SESSION.id')))));
      $f3->set('stats.wishs', count($this->model->getProducts(array('id_user'=>$f3->get('SESSION.id')))));
      $this->tpl['sync']='addContrib.html';

    }

    public function viewContrib($f3){
      /*
        View Contrib Page
        If contrib exists in database
        Show contrib's products 
      */
      $contrib=$this->model->getContrib(array('clef'=>$f3->get('PARAMS.clef'), 'id_user'=>$f3->get('SESSION.id')));
      if(count($contrib)>0){
        $f3->set('allProducts', $this->model->getProductsContrib(array('id_user'=>$f3->get('SESSION.id'), 'id_contrib'=>$contrib[0]['id_contrib'])));
        $f3->set('dons', $this->model->getDonsContrib(array('id_contrib'=>$contrib[0]['id_contrib'])));
        $f3->set('contrib', $contrib);
      }
      $f3->set('page', "follow");
      $f3->set('stats.nbfollowers', count($this->model->getfollowers(array('id_user'=>$f3->get('SESSION.id')))));
      $f3->set('stats.nbfollows', count($this->model->getfollows(array('id_user'=>$f3->get('SESSION.id')))));
      $f3->set('stats.wishs', count($this->model->getProducts(array('id_user'=>$f3->get('SESSION.id')))));
      $this->tpl['sync']='contrib.html';
    }

    public function likeArticleContrib($f3){

      $exist=$this->model->getArticleContrib(array('id_contrib'=>$f3->get('PARAMS.id_contrib'), 'id_article'=>$f3->get('PARAMS.id_article')));
      if(count($exist)!=0){
        $f3->set('status',$this->model->likeArticleContrib(array('id_contrib'=>$f3->get('PARAMS.id_contrib'),'id_article'=>$f3->get('PARAMS.id_article'),'id_user'=>$f3->get('SESSION.id'))));
      }
      if(!$f3->exists('status'))
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
      if(count($f3->get('PARAMS.id_article')) > 0){
        $article=$this->model->getArticle(array('id_article'=>$f3->get('PARAMS.id_article')));
        if(count($article)==1){
          $exist=$this->model->articleInMyWishlist(array('id_article'=>$f3->get('PARAMS.id_article'), 'id_user'=>$f3->get('SESSION.id')));
          $id_tag=$this->model->getTagDefault(array('id_user'=>$f3->get('SESSION.id')));
          $f3->set('status', $this->model->reWishlister(array('id_article'=>$f3->get('PARAMS.id_article'), 'id_user'=>$f3->get('SESSION.id'), 'date_souhait'=>date('Y-m-d H:i:s')), $id_tag['id_tag'])); 
        }
      }
      if(!$f3->exists('status'))
        $f3->set('status','0');
      $this->tpl['async']='json/status.json';  
    }

    public function partialAddContrib($f3){
      $f3->set('tags', $this->model->getUserTags(array('id_user'=>$f3->get('SESSION.id_user'))));
      $this->tpl['async']='partials/addContrib.html'; 
    }

    public function shareWishlist($f3){
      $f3->set('page', 'share');
      $f3->set('stats.nbfollowers', count($this->model->getfollowers(array('id_user'=>$f3->get('SESSION.id')))));
      $f3->set('stats.nbfollows', count($this->model->getfollows(array('id_user'=>$f3->get('SESSION.id')))));
      $f3->set('stats.wishs', count($this->model->getProducts(array('id_user'=>$f3->get('SESSION.id')))));

      $this->tpl['sync']='shareWishlist.html';
    }


}




?>