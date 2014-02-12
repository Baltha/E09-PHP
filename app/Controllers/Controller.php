<?php
class Controller{
  
protected $tpl;
protected $model;

  public function beforeroute(){
    $model=substr(get_class($this),0,strpos(get_class($this),'_')+1).'model';
    if(class_exists($model)){
      $this->model=new $model();
    }
  }
  
  
  public function afterroute($f3){
        
    $mimeTypes=array('html'=>'text/html','json'=>'application/json');
    $tpl=$this->tpl;
    $ext=substr($tpl,strrpos($tpl,'.')+1);
    $mime=$mimeTypes[$ext];
    echo View::instance()->render($tpl,$mime);

  } 
  
}
?>