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

  public function getUser($params){
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
  
  function parseProduct($params)
  {

    $homepage = file_get_contents($params['product']);
    $homepage = utf8_encode($homepage); 

    //Amazon
    if(preg_match('#amazon#',$params['product']))
    {
      echo 'AMAZON'.'<br />'; 
      preg_match('/id="btAsinTitle"\>\s*\<span[^>]*\>\s*([^<]*)\s*/is', $homepage, $matchesName);
      preg_match('/id="actualPriceValue"\>\s*\<b[^>]*\>\s*([^<]*)\s*/is', $homepage, $matchesPrice);
      preg_match('/class="productDescriptionWrapper"\>\s*(.*?)\s*\<div class="emptyClear"/is', $homepage, $matchesDescribe);
      preg_match('/<img id="main-image-nonjs" src="(.*?)"/is', $homepage, $matchesPicture);
      $nom=$matchesName[1];
      $price=$matchesPrice[1];
      $describe=$matchesDescribe[1];
      $picture=$matchesPicture[1];
      $tabResult=array('nom'=>$nom,'price'=>$price,'describe'=>$describe,'picture'=>$picture);
      //echo "<img src=".$matchesPicture[0]."><br />";
    }
    //RueDuCommerce
    else if(preg_match('#rueducommerce#', $params['product']))
    {
      echo 'RUE DU COMMERCE'.'<br />';
      preg_match('/class="brandNameSpan"\>\s*\<a href=".*?"\>(.*?)\<\/a\>\s*<\/span\>\s*(.*?)\<\/h1\>/is', $homepage, $matchesName);
      preg_match('/class="newPrice"\>\s*(.*?)\<sup\>\s*(.*?)\<\/sup\>/is', $homepage, $matchesPrice);
      preg_match('/class="ficheProduit_descriptionCourte"\>\s*(.*?)([^<br>]\s*[^<br \/>]*)\<\/div\>/is', $homepage, $matchesDescribe);
      preg_match('/class="photo"\>\s*\<a id="linkPhoto" target="_blank" href="(.*?)"\>/is', $homepage, $matchesPicture);
      $like =file_get_contents("http://graph.facebook.com/?id=".$params['product']);
      $like=json_decode($like);
      $nom =$matchesName[1].$matchesName[2];
      $price=$matchesPrice[1].$matchesPrice[2];
      $describe=$matchesDescribe[1];
      $picture=$matchesPicture[1];

      $tabResult=array('like'=>$like->{'shares'},'nom'=>$nom,'price'=>$price,'describe'=>$describe,'picture'=>$picture);
     // echo "Nb Like Facebook : ".$like->{'shares'}."<br />";
     // echo "<div style=width:500px;height:500px>".$matchesPicture[1] ."</div><br />";
    }
      return $tabResult;
  }
}
?>