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

  public function getAllUsers(){
    return $this->dB->exec('SELECT * FROM users WHERE id_user NOT LIKE 0');
  }

  public function verifNewUser($params){
     return $this->getMapper('users')->load(array('mail=?', $params['mail']));
  }


  public function setInfos($params){
  //Integrer le code déja fait de jerem ou louis, pour vérif si les deux mdp sont similaires, puis crypter si c'est le cas.
  //Je ne l'ai pas fait là mais en tps normal on peux aussi concaténer la chaine au fur et à mesure pour éviter de chercher à mettre à jour des champs que l'utilisateur n'a pas remplis, je fais plus simple en mettant en required les champs.
  //return $this->dB->exec('UPDATE users SET mdp='.$params['mdp'].',mail='.$params['mail'].',adresse="'.$params['adresse'].'",ville='.$params['ville'].',code_postal='.$params['code_postal']);
    $map = $this->getMapper('users')->load(array('id_user=?',$params['id_user']));
    foreach($params as $key => $param){
      $map->$key=$param;
    }
    $map->save();
  } 

  public function addUser($params){
    $map=$this->getMapper('users');
    foreach($params as $key => $param){
      $map->$key=$param;
    }
    $map->save();
  }

  public function addFollow($params){
    $map=$this->getMapper('amis');
    foreach($params as $key => $param){
      $map->$key=$param;
    }
    $map->save();
  }

  public function getfollows($params){
    return $this->getMapper('amis')->find(array('user_parent=?',$params['id_user']));
  }
  public function getfollowers($params){
    return $this->getMapper('amis')->find(array('user_enfant=?',$params['id_user']));
  }

  public function getfollowing($params){
    return $this->getMapper('amis')->load(array('user_parent=? AND user_enfant=?',$params['user_parent'],$params['user_enfant']));
  }


  public function addDefaultTag($id_user){
    $insert=$this->getMapper('tag');
    $insert->id_user=$id_user;
    $insert->nom='Toutes';
    $insert->date_tag=date('Y-m-d H:i:s');
    $insert->save();
  }

  public function password($mdp){
    return sha1('4txuadj6'.$mdp.'tx5hcv7f');
  }

  public function login($params){

    return $this->getMapper('users')->load(array('mail=? AND mdp=?',$params['login'],$params['password']));
   }
  
 public function parseProduct($params)
  {
    $web=new Web;
    $url=$params['product'];
    $request=$web->request($url);

    $homepage = $request['body'];

    $homepage = utf8_encode($homepage); 
    /*if(empty($url))
    {
      return; 
    }*/
   
    if(preg_match('#amazon#',$url))
    {

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
      preg_match('/itemprop="identifier" content="mpn:(.*?)"/is',$homepage,$matcheId);
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
    else if(preg_match('#3suisses#', $url))
    {

      preg_match('/R=([^#]*)&fac/is',$url,$matcheId);
      if(empty($matcheId[1]))
      {
        preg_match('/R=([^#]*)/is',$url,$matcheId);
      }
      
      preg_match('/<h1 itemprop="name"\>\s*(.*?)\<\/h1\>/is', $homepage, $matchesName);
      preg_match('/<meta itemprop="price" content="(.*?)"/is', $homepage, $matchesPrice1);
      preg_match('/class="pLeft"\>\s*(.*?)([^\<br\>][^\<br \/>]*)\<\/div\>/is', $homepage, $matchesDescribe);
      preg_match('/<img class="visuel" .*? src="(.*?)"/is', $homepage, $matchesPicture);
      $id=$matcheId[1];
      $nom =$matchesName[1];
      $price=$matchesPrice1[1];
      $describe=$matchesDescribe[1];
      $picture=$matchesPicture[1];
      $tabResult=array('nom'=>$nom,'price'=>$price,'describe'=>$describe,'picture'=>$picture, 'link'=>$url, 'qid'=>$id);

    }
    if(empty($id))
    {
      throw new Exception("Url incorrecte", 1);  
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
        $lastIdArticle=$this->dB->exec('SELECT LAST_INSERT_ID() AS id From article LIMIT 1');
        $lastIdArticle=$lastIdArticle[0]['id'];
       }
       else
       {
         //Le produit existe déja en base, n'oublions pas de créer le souhait quand même, récupérations de l'id produit
         $lastIdArticle=$testQid[0]['id'];
       }
       //Création d'un souhait
     
        $this->dB->exec(
        array(
              'INSERT INTO souhait (id_user,id_article) VALUES (?,?)'),
        array(
                  array(1=>$params['id_user'],2=>$lastIdArticle)
                )
        );

        $lastIdSouhait=$this->dB->exec('SELECT LAST_INSERT_ID() AS id_souhait From souhait LIMIT 1');
        $lastIdSouhait=$lastIdSouhait[0]['id_souhait'];

     // Ajout du tag
      if(isset($params['tag']))
      {
        $idTag = $this->getMapper('tag')->load(array('nom=? && id_user=?', $params['tag'], $params['id_user']));
        
        $idTag = $idTag["fields"]["id_tag"]["value"];

        $this->dB->exec(
          array(
                'INSERT INTO appartenance (id_souhait,id_tag) VALUES (?,?)'),
          array(
                    array(1=>$lastIdSouhait,2=>$idTag)
               )
        );
      }
      
    return $lastIdArticle;
  }

  public function addTag($params){
    $map=$this->getMapper('tag');
    foreach($params as $key => $param){
      $map->$key=$param;
    }
    $map->save();
  }

  public function getProducts($params)
  {  
     $allproducts=$this->dB->exec('SELECT * FROM souhait s LEFT JOIN article a ON s.id_article=a.id_article WHERE s.id_user='.$params['id_user'].' ORDER BY id_souhait DESC');
     return $allproducts;
  }

  public function lastProduct($params)
  {  
     $lastIdSouhait = $this->dB->exec('SELECT MAX(id_souhait) AS id From souhait WHERE id_user='.$params['id_user'].' LIMIT 1');
     return $this->dB->exec('SELECT * FROM souhait s LEFT JOIN article a ON s.id_article=a.id_article WHERE s.id_user='.$params['id_user'].' AND s.id_souhait='.$lastIdSouhait[0]['id']);
     
  }

  public function getUserTags($params)
  {
    return $this->getMapper('tag')->find(array('id_user=?',$params['id_user']));
  }

  public function getProductTags($params)
  {
    return $this->dB->exec('SELECT * FROM appartenance a LEFT JOIN tag t ON a.id_tag=t.id_tag WHERE a.id_souhait='.$params['id_souhait']);
  }

  public function deleteProduct($params)
  {
    $this->dB->exec('DELETE FROM souhait WHERE id_souhait='.$params['id_souhait']);
    return '1';
  }

  public function getMycContribution($params){
    return $this->dB->exec('SELECT DISTINCT c.* FROM contrib c LEFT JOIN don d ON d.id_contrib=c.id_contrib WHERE d.id_user=:id OR c.user_createur=:id', array('id'=>$params['id_user']));
  }

  public function addDon($params){
    $map=$this->getMapper('don');
    foreach($params as $key => $param){
      $map->$key=$param;
    }
    $map->save();
  }

  function searchUser($params){
    $query='(nom like "%'.$params['keywords'].'%" or prenom like "%'.$params['keywords'].'%")';
    return $this->getMapper('users')->find($query);
  }

}
?>