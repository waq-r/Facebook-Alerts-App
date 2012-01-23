#!/usr/local/bin/php -q
<?php
//require_once('nette.min.php');
//Nette\debug::enable(Nette\debug::DEVELOPMENT);

// First we set our procedence, so that we can only visit this page and not directly the class or config file.
$proc = TRUE;
// Including config file and mysql class.
include('xmysqli/config.php');
include('xmysqli/mysql.class.php');
ob_start();
$eachmail = new Emailparse;


 Class EmailParse {
	 //public $email; // This will be the variable holding the data.
	public $fd;
	public $msg;
	public $dbLink;
	public $smail;
	public $body;

	public function __construct(){

		/* Read the message from STDIN */
		$this->fd = fopen("php://stdin", "r");
		while (!feof($this->fd)) {
		$mailTxt .= fread($this->fd, 1024);
		}
		fclose($this->fd);

		$mail = mailparse_msg_create();
		mailparse_msg_parse($mail,$mailTxt);
		$msgpart = mailparse_msg_get_part_data($mail);

preg_match("/[\w\.\-]+@([\w\-]+\.)+[a-zA-Z]+/", $msgpart['headers']['from'], $emailFrom1);
$m['from'] =  $emailFrom1[0];
$m['subject'] = $msgpart['headers']['subject'];
$m['to'] = $this->matchEmail($msgpart['headers']['to']);
$m['boundry'] = preg_match("/=[a-z0-9_]+/", $msgpart['headers']['content-type'], $boundry);
$m['boundry'] = str_replace("=", "", $boundry[0]);

$mb = substr($mailTxt, $msgpart["starting-pos-body"], $msgpart["ending-pos-body"]);
$mb = preg_split("/".$m['boundry']."/", $mb, 3);
$mb = $mb[1];

$this->msg = $m;
$this->body = $mb;
$this->dbLink = new DB_mysql;
$this->mailType();

unset($msgpart, $mailTxt, $mail, $emailFrom, $boundry);
		}
  
		 function matchEmail($subject){
			if(preg_match("/[\w\.\-]+@([\w\-]+\.)+[a-zA-Z]+/", $subject, $email))
				{ 
				return $email[0];
				}
			else { return NULL;
				}
			}
	  
	  		function getDueDate($bdy){
			if(preg_match("/on[ ]+([0-9]{1,2})[ ]+[JFMASOND]+[a-z]{2}/", $bdy, $ddate)){
				preg_match("/[JFMASOND]+[a-z]{2}/", $ddate[0], $mm);
				preg_match("/[0-9]{1,2}/", $ddate[0], $dd);
			
			$month = array('Jan'=>1,'Feb'=>2,'Mar'=>3,'Apr'=>4,'May'=>5,'Jun'=>6,'Jul'=>7,'Aug'=>8,'Sep'=>9,'Oct'=>10,'Nov'=>11,'Dec'=>12);	
			$duedate = date("Y")."-".$month[$mm[0]]."-".$dd[0];
			//			$duedate = date("Y")."-".$ddate[1]."-".$month[$ddate[3]];
			return $duedate;
			}
			
			if(preg_match("/[\d]{2}\/[\d]{2}\/[\d]{2}[\s][\d]{2}\:.*/", $bdy, $ddate)){
			$ddate = explode(" ", $ddate[0]);	
			$duedate = explode('/', $ddate[0]);
			return "20".$duedate[2]."-".$duedate[1]."-".$duedate[0];
			}
			
			
			
		}

	  	// get barcodes and titles of due books
		function getBookDetails($subject){
		//get all books listed in email
		if(preg_match_all("/.*[ ]+(\.)[ ](\-)[ ]+.*/", $subject, $boook)){

		foreach ($boook[0] as $title) {
			//splitting barcode and title apart
			$titleno = preg_split("/([0-9])[ ]+/", $title, 2);
			if(preg_match("/[0-9]+/", $titleno[0], $title2no)){
			//setting barcodes as key to the titles, in book array
			if (!isset($book[$title2no[0]])) { 
				$book[$title2no[0]] = substr($titleno[1], 0, 59);
				}
			}
			else {$book = NULL; 
			echo 'cannot split "'.$titleno.'" into barcode n title ';}
		}
	}
	else {$book = NULL;
		echo 'cannot match "'.$subject.'" for barcode n title ';}
		
			return $book;
		}
		function getRecallBooks($subject){
			//get all books listed in email
			if(preg_match("/[0-9]+[ ].*Shelfmark:/", $subject, $bcode)){
			$bcode = preg_replace("/[\D]/", "", $bcode[0]);
			$book[$bcode] = 'untitled';
				if(preg_match("/.*[ ]+\/[ ]+.*/", $subject, $btitle)){
				$book[$bcode] = substr($btitle[0], 0, 59);
				}
			}
			else {$book = NULL;
				echo 'cannot match "'.$subject.'" for barcode n title ';
				}
			return $book;
		}
		// get library no of user
		function getLibraryNo($subject){
			preg_match("/[Nn]umber:[\s][\d]+/", $subject, $libno);
			$libno = preg_replace("/[\D]/", "", $libno[0]);
			return $libno;
		}
      //if it is recall/reminder/confimation or other message
	function sortEmailSub($subject) {
			if(preg_match("/\(\#[0-9]{2,}[\)] | Forwarding Confirmation/", $subject, $emailType)){
			$emailSort = 'confirm';
			}
			elseif(preg_match("/LOAN[ ]+REMINDER/", $subject, $emailType)){
			$emailSort = 'reminder';
			}
		elseif(preg_match("/RECALLED[ ]+BOOKS/", $subject, $emailType)){
			$emailSort = 'recall';
			}

		else {
			$emailSort = Null;
		}

		return $emailSort;
		}

      function getConfirmLink($bdy){
		  //http://mail.google.com/a/myport.ac.uk/vf-
          if(preg_match("/http[s]?:\/\/mail.google.com\/(.*)/", $bdy, $confirm_link)) {
          $confirmLink = $confirm_link[0];
			}
			else {
				$confirmLink = NULL;
				echo 'regex for confirm link failed';
				}
			return $confirmLink;
		}
      function getConfirmCode($subject){
          if (preg_match("/\(\#[0-9]{2,}[\)]/", $subject, $confirm_code)){
			$confirmCode = $confirm_code[0];
			}
			else {
			$confirmCode = NULL;
			//throw new regExFailException("can't get confirm code from subject '$subject'");
			}
	  return preg_replace('/(\D+)/', '',$confirmCode);
      }

	function sendConfirmCode(){
		$forwardersEmail = $this->matchEmail($this->msg['subject']);
		if($forwardersEmail) {
			$confirmCodeMail['to'] = $forwardersEmail;
			//set forwarding confirmation link
			$confirmCodeMail['link'] = $this->getConfirmLink($this->body);
			//set forwarding confirm code
			$confirmCodeMail['code'] = $this->getConfirmCode($this->msg['subject']);
			
		}
			return $confirmCodeMail;
	}
	
	function nonLibMail(){	
	//check and save subject if user opted for all emails subjects delivered in fb
	$chkAll = $this->dbLink->query("select all_emails from usr where email = '".$this->msg['to']."' OR email2 = '".$this->msg['to']."'");
	echo 'all email '.$chkAll[0]['all_emails'];
	if($chkAll[0]['all_emails'] == 1) {
	$chkAll = $this->dbLink->query("INSERT INTO `mail` (`from`, `subject`, `date`) VALUES ('".$this->msg['to']."', '".addslashes(substr(mb_decode_mimeheader($this->msg['subject']),0,74))."', now())");
	}
	echo 'its not confirm/recal/reminder';
	}
	
	function sendMail(){
		
	$this->smail['headers'] = 'From: LibraryAlertsApp@techploited.com' . "\r\n" .
    'Reply-To: no-reply@techploited.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
    //this array holds confirm code and link
    $this->smail['lc'] = $this->sendConfirmCode();
	$this->smail['body'] = "Hi,\n
Your email forwarding request to Facebook is been receieved, to validate forwarding please follow the confirmation link or use confirmation code below in your Google mail > settings > Forwarding \n \n
Confirmation link: ".$this->smail['lc']['link']."\n
Confirmation code: ".$this->smail['lc']['code']."\n \n
Thanks,\nUoP Facebook Library Alerts App \n";
	
$this->smail['subject'] = "Your email forwarding confirmation";
mail($this->smail['lc']['to'], $this->smail['subject'], $this->smail['body'], $this->smail['headers']);
echo '<pre> $this->smail ';
var_dump($this->smail);
echo '</pre>';
//save in db

$this->dbLink->query("INSERT INTO confirm (email, code, link, date) values ('".$this->smail['lc']['to']."', ".$this->smail['lc']['code'].", '".$this->smail['lc']['link']."', now())");

	echo 'confirmed ';
	}
	
	function saveAlert($type){
		$bd = NULL;
		if($type == 'reminder'){
		$bd = $this->getBookDetails($this->body);
		}
		if($type == 'recall'){
		$bd = $this->getRecallBooks($this->body);
		}
	$ln = $this->getLibraryNo($this->body);
	$dd = $this->getDueDate($this->body);
	
	if(isset($bd, $ln)) {
	echo '<pre> book details $bd';
	var_dump($bd);
	echo 'lin no '.$ln.'</pre>';
	foreach($bd as $key=>$val){
		$bchk = $this->dbLink->query("select barcode from books where barcode = ".$key."");
		if ($bchk == FALSE) {
		$this->dbLink->query("insert into books (barcode, title) values (".$key.",'".$val."')");
		}
	$this->dbLink->query("INSERT INTO `alerts` (`lib_no`, `type`, `barcode`, `date`, `due_date`, `read`) VALUES ('".$ln."', 'reminder', '".$key."', now(), '".$dd."', '0')");
	//$dbLink->query("insert into alerts (lib_no, type, barcode, date, due_date, read) values('".$ln."', 'reminder', '', '', '' '0')");
	}
	}
	else {
		echo ' book details/lib no $bd, $ln found saving lib mail ';
		$this->saveLibMail();
		}
	}
	
	function saveLibMail(){

	$this->dbLink->query("INSERT INTO `lib_emails` (`email`, `email_sub`, `email_body`, `date`)
VALUES ('".$this->msg['from']."', '".$this->msg['subject']."', '".addslashes(substr($this->body, 0, 999))."', now())");
	}
	
	function mailType(){
		  $type = $this->sortEmailSub($this->msg['subject']);
  echo '<pre>mail header $msg';
  var_dump($this->msg);
  echo '</pre>';
if (isset($type)) {
echo '<pre>';
var_dump($type);


//SWITCH TYPE OF EMAIL
switch($type) {
	//REMINDER
	case 'reminder':
$this->saveAlert($type);
	break;
	
	//RECALL
	case 'recall':
$this->saveAlert($type);
	break;
	
	//CONFIRM
	case 'confirm':
	$this->sendMail();
	break;
}
}
else {
	$this->nonLibMail();
	}
	}
	
	public function __destruct(){
	}
}
  
  //$msg = $eachmail->msg;
 
/*  $body1 = preg_split("/".$msg['boundry']."/", $msg['body'], 3);
  echo '<pre>';
var_dump($body1[1]);
echo '</pre>';
  $body = $body1[1];
 // unset($msg['body'], $body1);
 */

	/*
	//find email boundry to select first 1000 chars from boundry and save in DB
	preg_match("/=[a-z0-9_]+/", $msgpart['headers']['content-type'], $boundry);
	str_replace("=", "", $boundry[0]);
	$st = strpos($body, $boundry[0])+strlen($boundry[0])+1;
	$dbLink->query("INSERT INTO `lib_emails` (`email`, `email_sub`, `email_body`, `date`)
VALUES ('".$msg['from']."', '".$msg['subject']."', '".addslashes(substr($body,$st, $st+999))."', now())");

    echo '<pre>';
var_dump($boundry, $msgpart['headers']['content-type'], $st);
echo '</pre>';
* /

/*
$man = $dbLink->query("SELECT `email_body` FROM `lib_emails` where email = 'support@manplay.com'");
$spos = strpos($man[0]['email_body'], "<body>");
echo substr($man[0]['email_body'], $spos+6, 999);
*/





     /*   if (strlen($msg['subject']) > 70) {
            $msg['subject'] = substr($msg['subject'], 0,70);
            $msg['subject'] = $msg['subject']."...";
		}
        if(!is_null($msg['from'] | $msg['subject'])){
          dibi::query('INSERT INTO [mail]', $msg);
		}
		else {
			echo "saving whole email because regex failed to fetch";
		}
*/

		//$dbLink->query('INSERT INTO log(date,log) VALUES (now(), "asdf")');

// BUT! Oh no, we have a few invalid queries, let's take a look at our first auxiliar array: 
if (isset($dbErrors)) {
  $num_errors = count($dbErrors);
  echo '</pre><h3>All the queries executed, but we have some errors!</h3><pre>';
  print_r($dbErrors);
  echo '</pre><hr><pre>';
}
else $num_errors = 0;



/*
Class EmailParts {
	//public $email; // This will be the variable holding the data.
	public $fd;
	//public $fdw;

	public function __construct(){

		// Read the message from STDIN 
		$this->fd = fopen("php://stdin", "r");
}

public function mailToVar(){
		while (!feof($this->fd)) {
		$mailTxt .= fread($this->fd, 1024);
		}
		fclose($this->fd);

		$mail = mailparse_msg_create();
		mailparse_msg_parse($mail,$mailTxt);
		$msgpart = mailparse_msg_get_part_data($mail);

		$msgHeader['to'] = $msgpart['headers']['to'];
		$msgHeader['subject'] = $msgpart['headers']['subject'];
		$msgHeader['from'] = $msgpart['headers']['from'];

		return $msgHeader;
	}

}
ob_start();

$everymail = new EmailParts;
$mailHeader = $everymail->mailToVar();
echo '<pre>';
var_dump($mailHeader);
echo '</pre>';

$content = ob_get_contents();
ob_clean();

$fdw = fopen(__DIR__ . "/mail.html", "w+");
fwrite($fdw, $content);
fclose($fdw);
*/



/* Script End */

/* Read the message from STDIN 
$fd = fopen("php://stdin", "r");
$email = ""; // This will be the variable holding the data.
while (!feof($fd)) {
$email .= fread($fd, 1024);
}
fclose($fd);


$fdw = fopen(__DIR__ . "/mail8.txt", "w+");
fwrite($fdw, $email);
fclose($fdw);
 Script End */




//write errors and other messages if any to mail log of each mail
echo date("d/m/y : H:i:s", time()).'<hr /> ';
$content = ob_get_contents();
ob_clean();

//write time stamp and compare with current date to empty old records
//$ts = fopen(__DIR__ . "/timestamp.txt", "r");
if(file_get_contents(__DIR__ . "/timestamp.txt") !== date("d/m/y")) {
file_put_contents(__DIR__ . "/timestamp.txt", date("d/m/y"));
$fhandle = "w+";
}
else {$fhandle = "a+"; }
//write log file
$fdw = fopen(__DIR__ . "/mail.html", $fhandle);
fwrite($fdw, $content);
fclose($fdw);


//unset($file, $file_txt, $body, $msg, $parse, $type, $msgpart, $headers, $sendCodeBack);
