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
    $url=$params['product'];
    $request=$web->request($url);

    $homepage = $request['body'];

    $homepage = utf8_encode($homepage); 
   
    if(preg_match('#amazon#',$url))
    {
      echo 'AMAZON'.'<br />'; 

      preg_match('/product\/([^\/]*)/is',$url,$matcheId);
      if(!isset($matcheId[1]))
      {
        preg_match('/dp\/([^\/]*)/is',$url,$matcheId);
      }
      $id=$matcheId[1];
      preg_match('/id="btAsinTitle"\>\s*\<span[^>]*\>\s*([^<]*)\s*/is', $homepage, $matchesName);
      if(!isset($matchesName[1]))
      {
        preg_match('/id="btAsinTitle"\>(.*?)\<\/span\>/is', $homepage, $matchesName);
      }
        $nom=$matchesName[1];

      preg_match('/id="actualPriceValue"\>\s*\<b[^>]*\>EUR ([^<]*)\s*/is', $homepage, $matchesPrice); 
      if(!isset($matchesPrice[1]))
      {
        //On est sur un produit Phare d'Amazon, on le traite différement.
        preg_match('/id="buyingPriceValue"\>\s*\<b[^>]*\>EUR ([^<]*)\s*/is', $homepage, $matchesPrice); 
      }
        $price=$matchesPrice[1];
        $price=(float)str_replace(',', '.', $price);

      preg_match('/class="productDescriptionWrapper"\>\s*(.*?)\s*\<div class="emptyClear"/is', $homepage, $matchesDescribe);
      
      if(!isset($matchesDescribe[1]))
      {
        preg_match('/id="kindle-feature-bullets-atf"\>\s*(.*?)\s*\<\/div\>/is', $homepage, $matchesDescribe);
          
      }
      if(isset($matchesDescribe[1]))
      {
        $describe=$matchesDescribe[1];
      }
      else
      {
        $describe="Pas de description";
      }
        
      
      preg_match('/<img id="main-image-nonjs" src="(.*?)"/is', $homepage, $matchesPicture);
      if(!isset($matchesPicture[1]))
      {
        preg_match('/id="kib-ma-container-0" .*?\>\s*<img class="kib-ma kib-image-ma" .*? src="(.*?)" /is', $homepage, $matchesPicture);
      }
        $picture=$matchesPicture[1];

      $tabResult=array('nom'=>$nom,'price'=>$price,'describe'=>$describe,'picture'=>$picture, 'link'=>$url, 'qid'=>$id);
    }
    //RueDuCommerce
    else if(preg_match('#rueducommerce#', $url))
    {
      echo 'RUE DU COMMERCE'.'<br />';
      preg_match('/mpid:([^#]*)/is',$url,$matcheId);
      preg_match('/class="brandNameSpan"\>\s*\<a href=".*?"\>(.*?)\<\/a\>\s*<\/span\>\s*(.*?)\<\/h1\>/is', $homepage, $matchesName);
      preg_match('/class="newPrice"\>\s*(.*?)\<sup\>&euro;(.*?)\<\/sup\>/is', $homepage, $matchesPrice);
      preg_match('/class="ficheProduit_descriptionCourte"\>\s*(.*?)([^\<br\>][^\<br \/>]*)\<\/div\>/is', $homepage, $matchesDescribe);
      preg_match('/class="photo"\>\s*\<a id="linkPhoto" target="_blank" href="(.*?)"\>/is', $homepage, $matchesPicture);
      // $like =file_get_contents("http://graph.facebook.com/?id=".$url);
      //$like=json_decode($like);
      $id=$matcheId[1];
      $nom =$matchesName[1].$matchesName[2];
      $price=(float)($matchesPrice[1].'.'.$matchesPrice[2]);
      $describe=$matchesDescribe[1];
      $picture=$matchesPicture[1];
      //'like'=>$like->{'shares'}
      $tabResult=array('nom'=>$nom,'price'=>$price,'describe'=>$describe,'picture'=>$picture, 'link'=>$url, 'qid'=>$id);

    }
    return $tabResult;
  }

  public function addProduct($params)
  {
    //Requete de verif contre Duplicate Content.
    $testQid=$this->dB->exec(
      array(
            'SELECT id_article As id FROM article WHERE qid LIKE ?'),
      array(
                array(1=>$params['product']['qid'])
              )
      );

    if(!isset($testQid[0]['id']))
    {
      if(!isset($params['product']['like']))
      {
        $params['product']['like']=0;
      }
      $this->dB->exec(
        array(
              'INSERT INTO article (nom,description,prix,image,nblike,lien,qid) VALUES (?,?,?,?,?,?,?)'),
        array(
                  array(1=>$params['product']['nom'],2=>$params['product']['describe'],3=>$params['product']['price'],4=>$params['product']['picture'],5=>$params['product']['like'],6=>$params['product']['link'],7=>$params['product']['qid'])
                )
        );
     $lastId=$this->dB->exec('SELECT LAST_INSERT_ID() AS id From article LIMIT 1');
     $lastId=$lastId[0]['id'];
    }
    else
    {
      //Le produit existe déja en base, n'oublions pas de créer le souhait quand même, récupérations de l'id produit
      $lastId=$testQid[0]['id'];
    }
   //Création d'un souhait
   
      $this->dB->exec(
      array(
            'INSERT INTO souhait (id_user,id_article) VALUES (?,?)'),
      array(
                array(1=>$params['id_user'],2=>$lastId)
              )
      );
      return $lastId;
  }

  public function getProducts($params)
  {  
     $allproducts=$this->dB->exec('SELECT * FROM souhait s LEFT JOIN article a ON s.id_article=a.id_article WHERE s.id_user='.$params['id_user']);
     return $allproducts;
  }

  public function getUserTags($params)
  {  
     return $this->dB->exec('SELECT * FROM tag WHERE id_user='.$params['id_user']);
  }

  public function getProductTags($params)
  {
    return $this->dB->exec('SELECT * FROM appartenance a LEFT JOIN tag t ON a.id_tag=t.id_tag WHERE a.id_souhait='.$params['id_souhait']);
  }

  public function deleteProduct($params)
  {
    return $this->dB->exec('DELETE FROM souhait WHERE id_souhait='.$params['id_souhait']);
  }
}
?>