<?php
class App_model extends Model{
  
  
  function __construct(){
    parent::__construct();
  }
  
  function home(){
    
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
      //echo "<img src=".$matchesPicture[0]."><br />";
    }
    //RueDuCommerce
    else if(preg_match('#rueducommerce#', $params['product']))
    {
      echo 'RUE DU COMMERCE'.'<br />';
      preg_match('/class="brandNameSpan"\>\s*(.*?)\<\/div\>/is', $homepage, $matchesName);
      preg_match('/class="newPrice"\>\s*(.*?)\<\/span\>/is', $homepage, $matchesPrice);
      preg_match('/class="ficheProduit_descriptionCourte"\>\s*(.*?)\<\/div\>/is', $homepage, $matchesDescribe);
      preg_match('/class="photo"\>\s*(.*?)\<\/div\>/is', $homepage, $matchesPicture);
      $like =file_get_contents("http://graph.facebook.com/?id=".$params['product']);
      $like=json_decode($like);
     // echo "Nb Like Facebook : ".$like->{'shares'}."<br />";
     // echo "<div style=width:500px;height:500px>".$matchesPicture[1] ."</div><br />";
    }
    
      //Affichage de l'ensemble des résultats (excepte Les likes/et les photos)
      $nom=$matchesName[1];
      $price=$matchesPrice[1];
      $describe=$matchesDescribe[1];
          
      //echo $nom ."<br />";
      //echo $price ."<br />";
      //echo $describe ."<br />";
      $tabResult=array('nom'=>$nom);
      return $tabResult;
  }
  // function getUsers($params){
  //   return $this->getMapper('wifiloc')->find(array('promo=?',$params['promo']),array('order'=>'lastname'));
  // }
  
  // function getUser($params){
  //   return $this->getMapper('wifiloc')->load(array('userId=?',$params['name']));
  // }
  
  // function searchUsers($params){
  //   $query='(firstname like "%'.$params['keywords'].'%" or lastname like "%'.$params['keywords'].'%")';
  //   $query.=$params['filter']?' and promo="'.$params['filter'].'"':'';
  //   return $this->getMapper('wifiloc')->find($query);
  // }
  // public function favorite($params){
  //   $map=$this->getMapper('wififav');
  //   $favorite=$map->load(array('favId=? and logId=?',$params['favId'],$params['logId']));
  //   if(!$favorite){
  //     $map->favId=$params['favId'];
  //     $map->logId=$params['logId'];
  //     $map->save();
  //     return true;
  //   }else{
  //     $favorite->erase();
  //     return false;
  //   }

  //  }
  
}
?>