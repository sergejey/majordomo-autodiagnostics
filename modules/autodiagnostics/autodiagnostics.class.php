<?php
/**
* Auto Diagnostics 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 23:05:34 [May 21, 2018])
*/
//
//
class autodiagnostics extends module {
/**
* autodiagnostics
*
* Module class constructor
*
* @access private
*/
function autodiagnostics() {
  $this->name="autodiagnostics";
  $this->title="Auto Diagnostics";
  $this->module_category="<#LANG_SECTION_SYSTEM#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=1) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {

    if (gr('ok_msg')) {
        $out['OK_MSG']=gr('ok_msg');
    }

 $this->getConfig();
 $out['LINK_CODE']=$this->config['LINK_CODE'];
 $out['POST_EVERY']=$this->config['POST_EVERY'];

    if (!$out['LINK_CODE']) {
        $out['LINK_CODE']=base_convert(uniqid(mt_rand(), true), 16, 36);
    }

 if ($this->view_mode=='send_data') {
     $this->sendDataNow();
     $this->redirect("?ok_msg=".urldecode('Data sent'));
 }

 if ($this->view_mode=='update_settings') {
   $this->config['LINK_CODE']=gr('link_code');
   $this->config['POST_EVERY']=gr('post_every');
   $this->saveConfig();
   $this->redirect("?ok_msg=".urldecode(LANG_DATA_SAVED));
 }
}

function sendDataNow() {
    DebMes("Sending out diagnostics data",'diagnostics');

    $locale = !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? strip_tags($_SERVER['HTTP_ACCEPT_LANGUAGE']) : 'unknown';

    $fields=array(
      'code'=>$this->config['LINK_CODE'],
      'send'=>'1',
      'comments'=>'Auto Diagnostics'
    );
    
    $url = BASE_URL.'/diagnostic.php';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept-Language: $locale"));
    $result = curl_exec($ch);

    DebMes("Server response: \n".$result,'diagnostics');

    if ($result!='') {
        return json_decode($result,true);
    } else {
        return array();
    }
}

/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
 function processSubscription($event, $details='') {
 $this->getConfig();
  if ($event=='HOURLY') {
      $h=(int)date('H');
      DebMes("Processing HOURLY event with configured every ".$this->config['POST_EVERY']." and the H is $h",'diagnostics');
      if ($this->config['POST_EVERY']=='6' && ($h==2 || $h==8 || $h==14 || $h==20)) {
          $this->sendDataNow();
      } elseif ($this->config['POST_EVERY']=='12' && ($h==2 || $h==14)) {
          $this->sendDataNow();
      } elseif ($this->config['POST_EVERY']=='24' && ($h==2)) {
          $this->sendDataNow();
      }
  }
 }
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  subscribeToEvent($this->name, 'HOURLY');
  parent::install();
 }
 /**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  unsubscribeFromEvent($this->name, 'HOURLY');
  parent::uninstall();
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgTWF5IDIxLCAyMDE4IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
