<?php

require 'facebook.php';
$proc = TRUE;
// Including config file and mysql class.
include('../xmysqli/config.php');
include('../xmysqli/mysql.class.php');
echo '<link rel="stylesheet" type="text/css" href="style.css" />';
$fbLink = new fbLink;

Class fbLink {
	public $dbLink;
	public $session;
	public $nsession;
	public $uid;
	public $me;
	public $facebook;
	public $in;
	function __construct(){
		// Create our Application instance.

						

		if(isset($_POST['lib_no']) ){
			$this->in = $_POST;
			 }
			 //echo '<pre>'.var_dump($_POST).'</pre>';
		$this->facebook = new Facebook(array(
		  'appId' => '100189117367',
		  'secret' => '18f8e3dcb44daf55c609c7ed1d2a9142',
		  'cookie' => true,
		)); 
		$this->dbLink = new DB_mysql;
		echo '<pre>';
		$this->uid = $this->facebook->api('/me');
		var_dump($this->uid);
				echo '</pre>';
		$this->getSavedSession();
}

	function getNewSession(){
		$this->nsession = $this->facebook->getSession();


		$me = null;
		// Session based API call.
		if ($this->nsession) {
		  try {
			$this->uid = $this->facebook->getUser();
			$this->me = $this->facebook->api('/me');
		  } catch (FacebookApiException $e) {
			error_log($e);
		  }
		}
	}
	
	//compare user fbid with user_session table 
	//return null or saved session with access token
	function getSavedSession(){
		$usr = $this->dbLink->query("select access_token from usr_session where fb_id = '".$this->uid['id']."'");
		if(empty($usr)) {
			echo 'not set usr saving ';
			$this->saveSession();

		} else {
			print_r($usr);
			$this->session = $usr;
			$this->getLibUser();
		}
	}
	
	//save a session with sig, access_tocken etc
	function saveSession(){
		$nsess = $this->getNewSession();
		var_dump($nsess);
			$this->dbLink->query("INSERT INTO `usr_session` (`fb_id`, `sig`, `session_key`, `secret`, `expires`, `access_token`)
			VALUES ('".$this->nsession['uid']."', 
			'".$this->nsession['sig']."', 
			'".$this->nsession['session_key']."', 
			'".$this->nsession['secret']."', 
			'".$this->nsession['expires']."', 
			'".$this->nsession['access_token']."')");
			
			$this->saveUser();
			
			//$this->token = 
	}
	
	//increment facebook left-side alert menu counter
	function fbCounterInc(){
			$this->facebook->api(array(
			'method' => 'dashboard.incrementCount',
			'uid' => $this->session['uid'],
			'access_token' => $this->session['access_token']
			));
	}
	
	//set fb alert counter to zero upon user visit
	function fbCounterSet(){
			$this->facebook->api(array(
			'method' => 'dashboard.setCount',
			'count' => '0',
			'uid' => $this->session['uid'],
			'access_token' => $this->session['access_token']
			));
	}
	
	//check user registration comparing fb_id with usr table
	//check library number return null if not registered
	function getLibUser(){
		$usr = $this->dbLink->query("select lib_no from usr where fb_id = '".$this->uid['id']."'");
		print_r($usr);
		if(empty($usr)) {
			$this->saveUser();
		} else {
			$this->listAlerts();
		}
	}
	
	//register user with fb_id, lib_no
	function saveUser(){
							echo '<pre>'; var_dump($this->in); echo '</pre>';

		if(isset($this->in['lib_no'])){

			$adu = $this->dbLink->query("update usr set lib_no = '".$this->in['lib_no']."', email = '".$this->in['email']."', all_emails = '1' where fb_id ='".$this->nsession['uid']."' ");
			$this->setUserStatus();
			echo '<br /> You are registered now';
			$this->listAlerts();
			} else{
			$adu = $this->dbLink->query("INSERT INTO `usr` (`fb_id`, `lib_no`, `email`, `email2`, `all_emails`)
				VALUES ('".$this->nsession['uid']."', '0', '0', '0', '0')");
		echo '<h3> Register</h3>
		<h2>UoP library alerts in facebook</h2>
		<form method="POST" action="">
		<br /> UoP Library Number
		<input type="text" name="lib_no"/>
		<br /> Library Email
		<input type="text" name="email"/>
		<br /> Receieve subjects of all emails
		<input type="checkbox" name="all_emails" checked="yes" />
		<input type="submit" name="Submit" name="Signup" />
		</form>';
		}
		
	}
	
	//display user alerts and email subjects
	function listAlerts(){
		echo '<br /> list alert';
		$alert = $this->dbLink->query("select alerts.type, alerts.barcode, books.title, alerts.date, alerts.due_date, alerts.lib_no from alerts, books 
		where books.barcode = alerts.barcode and lib_no = 
		(select usr.lib_no from usr where usr.fb_id ='".$this->uid['id']."')");
		if($alert) {
		echo '<div class="list"><ul>Your Libray Alerts';
		foreach ($alert as $li) {
			echo '<li>'.$li['barcode'].' '
			.$li['title'].' <span id="dd">due on '			
			.$li['due_date'].'</span> '			
//			.$li['lib_no'].''
			.$li['date'].'</li>';
		}
		echo '</ul>';
		}
		$ml = $this->dbLink->query("select mail.subject, mail.date from mail where mail.from = 
		(select email from usr where fb_id ='".$this->uid['id']."') order by mail.date asc");
		if($ml)
		{
			echo '<ul>Your Emails';
			foreach ($ml as $mli) {
				echo '<li>'.$mli['subject'].'  '
				.$mli['date'].'</li>';
			}
			echo '</ul></div>';
		}
	}
	
	//delete or mar alerts as read
	function dismissAlerts(){
	}
	
	//update user facebook status with msg from app
	function setUserStatus(){
		$attachment =  array(
		'access_token' => $this->session['access_token'],
		 'message' => "started getting alerts of UoP libray loans on Facebook http://on.fb.me/l00opL",);
		$this->facebook->api('/'.$this->session['uid'].'/feed', 'POST', $attachment);
	}
}









if (isset($dbErrors)) {
  $num_errors = count($dbErrors);
  echo '</pre><h3>All the queries executed, but we have some errors!</h3><pre>';
  print_r($dbErrors);
  echo '</pre><hr><pre>';
}
else $num_errors = 0;
//*/

?>



