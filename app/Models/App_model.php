<?php
class App_model extends Model{
  
  
  function __construct(){
    parent::__construct();
  }
  
  function home(){
    
  }

  public function getUserFb($params){
    return $this->getMapper('users')->load(array('id_facebook=?', $params['id_facebook']));
  }

  // get user id after facebook signin
  public function getUserInfoAfterSignin($params){
    return $this->getMapper('users')->load(array('mail=?',$params['mail']));
  }

  public function getUser($params){
    return $this->getMapper('users')->load(array('id_user=?', $params['id_user']));
  }
  public function verifNewUser($params){
     return $this->getMapper('users')->load(array('mail=?', $params['mail']));
  }

  public function addUser($params){
    $map=$this->getMapper('users');
    foreach($params as $key => $param){
      $map->$key=$param;
    }
    $map->save();
  }


  public function password($mdp){
    return sha1('4txuadj6'.$mdp.'tx5hcv7f');
  }

  public function login($params){
    return $this->getMapper('users')->load(array('mail=?',$params['login']));
   }
  
 public function parseProduct($params)
  {
    $web=new Web;
    $request=$web->request($params['product']);

    $homepage = $request['body'];

    $homepage = utf8_encode($homepage); 

    //Amazon
    if(preg_match('#amazon#',$params['product']))
    {
      echo 'AMAZON'.'<br />'; 
      preg_match('/id="btAsinTitle"\>\s*\<span[^>]*\>\s*([^<]*)\s*/is', $homepage, $matchesName);
      preg_match('/id="actualPriceValue"\>\s*\<b[^>]*\>EUR ([^<]*)\s*/is', $homepage, $matchesPrice);
      preg_match('/class="productDescriptionWrapper"\>\s*(.*?)\s*\<div class="emptyClear"/is', $homepage, $matchesDescribe);
      preg_match('/<img id="main-image-nonjs" src="(.*?)"/is', $homepage, $matchesPicture);
      $nom=$matchesName[1];
      $price=$matchesPrice[1];
      $price=(float)str_replace(',', '.', $price);
      $describe=$matchesDescribe[1];
      $picture=$matchesPicture[1];
      $tabResult=array('nom'=>$nom,'price'=>$price,'describe'=>$describe,'picture'=>$picture, 'link'=>$params['product']);
    }
    //RueDuCommerce
    else if(preg_match('#rueducommerce#', $params['product']))
    {
      echo 'RUE DU COMMERCE'.'<br />';
      preg_match('/class="brandNameSpan"\>\s*\<a href=".*?"\>(.*?)\<\/a\>\s*<\/span\>\s*(.*?)\<\/h1\>/is', $homepage, $matchesName);
      preg_match('/class="newPrice"\>\s*(.*?)\<sup\>&euro;(.*?)\<\/sup\>/is', $homepage, $matchesPrice);
      preg_match('/class="ficheProduit_descriptionCourte"\>\s*(.*?)([^\<br\>][^\<br \/>]*)\<\/div\>/is', $homepage, $matchesDescribe);
      preg_match('/class="photo"\>\s*\<a id="linkPhoto" target="_blank" href="(.*?)"\>/is', $homepage, $matchesPicture);
      $like =file_get_contents("http://graph.facebook.com/?id=".$params['product']);
      $like=json_decode($like);
      $nom =$matchesName[1].$matchesName[2];
      $price=(float)($matchesPrice[1].'.'.$matchesPrice[2]);
      $describe=$matchesDescribe[1];
      $picture=$matchesPicture[1];
      $tabResult=array('like'=>$like->{'shares'},'nom'=>$nom,'price'=>$price,'describe'=>$describe,'picture'=>$picture, 'link'=>$params['product']);

    }
    return $tabResult;
  }

  public function addProduct($params)
  {

    if(!isset($params['product']['like']))
    {
      $params['product']['like']=0;
    }
   $this->dB->exec(
      array(
            'INSERT INTO article (nom,description,prix,image,nblike,lien) VALUES (?,?,?,?,?,?)'),
      array(
                array(1=>$params['product']['nom'],2=>$params['product']['describe'],3=>$params['product']['price'],4=>$params['product']['picture'],5=>$params['product']['like'],6=>$params['product']['link'])
              )
      );
   $lastId=$this->dB->exec('SELECT LAST_INSERT_ID() AS id From article LIMIT 1');
   //Création d'un souhait
   $this->dB->exec(
      array(
            'INSERT INTO souhait (id_user,id_article) VALUES (?,?)'),
      array(
                array(1=>$params['id_user'],2=>$lastId[0]['id'])
              )
      );
  return $lastId[0]['id'];
  }
  public function getProducts($params)
  {
    $souhaits = $this->dB->exec(
      'SELECT * FROM souhait '.
      'WHERE id_user = '.$params['id_user']
    );

    $allproducts = array();
    foreach ($souhaits as $souhait) {
      array_push($allproducts, $this->getMapper('article')->load(array('id_article=?',$souhait['id_article'])));
    }
    return $allproducts;
  }
}
?>