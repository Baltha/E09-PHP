<?php
class App_controller extends Controller{

  public function __construct(){
    //$this->tpl=array('sync'=>'main.html');
  }

  
  public function home($f3){
    $f3->set('SESSION.test', 'testeee');
    $this->tpl='main.html';
  }
  
  public function loginFb($f3){
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
          $f3->set('me', $facebook->api('/me'));
          $f3->set('friends', $facebook->api('/me/friends'));
        } 
        catch (FacebookApiException $e) {
          error_log($e);
          $f3->set('user', null);
        }
      }
    }

    if($f3->get('user')){
      // si user on va juste afficher le lien de déconnexion
      $f3->set('logoutUrl', $facebook->getLogoutUrl());
    } 
    else{
      $f3->set('statusUrl', $facebook->getLoginStatusUrl());
      $f3->set('loginUrl', $facebook->getLoginUrl(array('scope' => 'email, user_birthday')));
    }

    if($f3->exists('me')){
      // récupération et mise en variable des informations
      $param = array(
        'method' => 'fql.query',
        'query' => 'SELECT pic_small FROM user WHERE uid='.$f3->get('user'),
        'callback' => ''
      );
      $response = $facebook->api($param);
      $f3->set('me.pic_small', $response[0]['pic_small']);

      // envoie en BDD des informations ciblées
      $this->model->addUser(array(
        'nom'=>$f3->get('me.first_name'), 
        'prenom'=>$f3->get('me.last_name'),
        'naissance'=>date('Y-m-d', strtotime($f3->get('me.birthday'))),
        'mail'=>$f3->get('me.email'), 
        'sexe'=>$f3->get('me.gender'),
        'photo'=>$f3->get('me.pic_small'),
        'id_facebook'=>$f3->get('user')));
    }

    $this->tpl='facebook_connect.html';
  }

  public function inscription($f3){
    var_dump($_SESSION);
    if(!$f3->get('POST'))
      $this->tpl='inscription.html';
    else{
      $f3->set('erreur', array());

      if($f3->exists('POST.mail'))
        $mail=$f3->clean($f3->get('POST.mail'));
      else
        $f3->push('erreur', 'Mail manquant');

      if($f3->exists('POST.mdp'))
        $mdp=$f3->clean($f3->get('POST.mdp'));
      else
        $f3->push('erreur', 'Mot de passe manquant');

      if($f3->exists('POST.mdp2'))
        $mdp2=$f3->clean($f3->get('POST.mdp2'));
      else
        $f3->push('erreur', 'Confirmation du mot de passe manquant');

      if($f3->exists('POST.nom'))
        $nom=$f3->clean($f3->get('POST.nom'));
      else
        $f3->push('erreur', 'Nom manquant');

      if($f3->exists('POST.prenom'))
        $prenom=$f3->clean($f3->get('POST.prenom'));
      else
        $f3->push('erreur', 'Prénom manquant');

      if($f3->exists('POST.naissance'))
        $naissance=$f3->clean($f3->get('POST.naissance'));
      else
        $f3->push('erreur', 'Date de naissance manquante');

      if($f3->exists('POST.sexe'))
        $sexe=$f3->clean($f3->get('POST.sexe'));
      else
        $f3->push('erreur', 'Sexe manquant');

      if(empty($f3->get('erreur'))){
        // pas d'erreur on envoie
        // d'abord vérif si l'adresse mail est déjà présente dans la BDD dans ce cas on l'indique
        if($this->model->getUser(array('mail'=>$mail))==false){
          $this->model->addUser(array(
            'nom'=>$nom,
            'prenom'=>$prenom,
            'mdp'=>$this->model->password($mdp),
            'naissance'=>$naissance,
            'mail'=>$mail,
            'sexe'=>$sexe
            ));
        }
        else
          var_dump($f3->get('erreur'));
      }
      else
        var_dump($f3->get('erreur'));
      $this->tpl='main.html';
    }
  }
  
  public function parseProduct($f3){
    $product=$this->model->parseProduct(array('product'=>$f3->get('POST.product')));
    $f3->set('ESCAPE',FALSE);
    $f3->set('product',$product);

    $f3->set('SESSION.product',$product);
    $this->tpl='partials/contentProduct.html';
  }

  public function addProduct($f3){
    $this->tpl='main.html';
    $f3->set('product',$this->model->addProduct(array('nom'=>$f3->get('POST.nom'))));
    $f3->set('SESSION.product',array());
  }

}
?>