<?php
/*--------------------------------------------------
 | MIME Mail and SMTP Classes
 | By Devin Doucette
 | Copyright (c) 2004 Devin Doucette
 | Email: darksnoopy@shaw.ca
 +--------------------------------------------------
 | Email bugs/suggestions to darksnoopy@shaw.ca
 +--------------------------------------------------
 | This script has been created and released under
 | the GNU GPL and is free to use and redistribute
 | only if this copyright statement is not removed
 +--------------------------------------------------*/

class mimemail {
	var $headers		= array(
		'MIME-version'	=> "1.0",
		'Return-path'	=> "",
		'Date'			=> "",
		'From'			=> "",
		'Subject'		=> "",
		'To'			=> array(),
		'Cc'			=> array(),
		'Bcc'			=> array(),
		'X-Mailer'		=> "",
		'Content-type'	=> "",
	);
	var $message		= "This is a MIME encoded message.\r\n\r\n";
	var $charset		= "iso-8859-1";
	var $boundary		= array();
	var $filetypes		= array(
		'gif'	=> "image/gif",
		'jpg'	=> "image/jpeg",
		'jpe'	=> "image/jpeg",
		'jpeg'	=> "image/jpeg",
		'png'	=> "image/png",
		'bmp'	=> "image/bmp",
		'tif'	=> "image/tiff",
		'tiff'	=> "image/tiff",
		'swf'	=> "application/x-shockwave-flash",
		'wav'	=> "audio/wav",
	);
	var $attachtypes = array(
		'hqx'	=> "application/macbinhex40",
		'pdf'	=> "application/pdf",
		'pgp'	=> "application/pgp",
		'ps'	=> "application/postscript",
		'eps'	=> "application/postscript",
		'ai'	=> "application/postscript",
		'rtf'	=> "application/rtf",
		'xls'	=> "application/vnd.ms-excel",
		'pps'	=> "application/vnd.ms-powerpoint",
		'ppt'	=> "application/vnd.ms-powerpoint",
		'ppz'	=> "application/vnd.ms-powerpoint",
		'doc'	=> "application/vnd.ms-word",
		'dot'	=> "application/vnd.ms-word",
		'wrd'	=> "application/vnd.ms-word",
		'tgz'	=> "application/x-gtar",
		'gtar'	=> "application/x-gtar",
		'gz'	=> "application/x-gzip",
		'php'	=> "application/x-httpd-php",
		'php3'	=> "application/x-httpd-php",
		'js'	=> "application/x-javascript",
		'msi'	=> "application/x-msi",
		'swf'	=> "application/x-shockwave-flash",
		'rf'	=> "application/x-shockwave-flash",
		'tar'	=> "application/x-tar",
		'zip'	=> "application/zip",
		'au'	=> "audio/basic",
		'mid'	=> "audio/midi",
		'midi'	=> "audio/midi",
		'kar'	=> "audio/midi",
		'mp2'	=> "audio/mpeg",
		'mp3'	=> "audio/mpeg",
		'mpga'	=> "audio/mpeg",
		'voc'	=> "audio/voc",
		'vox'	=> "audio/voxware",
		'aif'	=> "audio/x-aiff",
		'aiff'	=> "audio/x-aiff",
		'aifc'	=> "audio/x-aiff",
		'wma'	=> "audio/x-ms-wma",
		'ra'	=> "audio/x-pn-realaudio",
		'ram'	=> "audio/x-pn-realaudio",
		'rm'	=> "audio/x-pn-realaudio",
		'ogg'	=> "audio/x-vorbis",
		'wav'	=> "audio/wav",
		'bmp'	=> "image/bmp",
		'dib'	=> "image/bmp",
		'gif'	=> "image/gif",
		'jpg'	=> "image/jpeg",
		'jpe'	=> "image/jpeg",
		'jpeg'	=> "image/jpeg",
		'jfif'	=> "image/jpeg",
		'pcx'	=> "image/pcx",
		'png'	=> "image/png",
		'tif'	=> "image/tiff",
		'tiff'	=> "image/tiff",
		'ico'	=> "image/x-icon",
		'pct'	=> "image/x-pict",
		'txt'	=> "text/plain",
		'htm'	=> "text/html",
		'html'	=> "text/html",
		'xml'	=> "text/xml",
		'xsl'	=> "text/xml",
		'dtd'	=> "text/xml-dtd",
		'css'	=> "text/css",
		'c'		=> "text/x-c",
		'c++'	=> "text/x-c",
		'cc'	=> "text/x-c",
		'cpp'	=> "text/x-c",
		'cxx'	=> "text/x-c",
		'h'		=> "text/x-h",
		'h++'	=> "text/x-h",
		'hh'	=> "text/x-h",
		'hpp'	=> "text/x-h",
		'mpg'	=> "video/mpeg",
		'mpe'	=> "video/mpeg",
		'mpeg'	=> "video/mpeg",
		'qt'	=> "video/quicktime",
		'mov'	=> "video/quicktime",
		'avi'	=> "video/x-ms-video",
		'wm'	=> "video/x-ms-wm",
		'wmv'	=> "video/x-ms-wmv",
		'wmx'	=> "video/x-ms-wmx",
		''		=> "application/octet-stream",
	);
	var $versionhtml	= "";
	var $versionplain	= "";
	var $parts			= array();
	var $files			= array();
	var $attachments	= array();
	var $errors			= array();

	function mimemail($flags=array()) {
		$this->headers['Date'] = date("D, d M Y H:i:s O",time()); // should be "...O (T)" but T sometimes returns the longer version of the time zone
		$this->boundary['mixed'] = md5(uniqid(microtime()));
		$this->boundary['related'] = md5(uniqid(microtime()));
		$this->boundary['alternative'] = md5(uniqid(microtime()));
	}

	function compilemail() {
		if((empty($this->headers['To']) && empty($this->headers['Cc']) && empty($this->headers['Bcc'])) || (empty($this->headers['From']) && empty($this->headers['Return-path'])))
			return $this->error("Some required headers are missing.");

		if($this->versionplain == "" && $this->versionhtml != "")
			$this->versionplain = strip_tags($this->versionhtml);

		if(!empty($this->attachments)) {
			$this->headers['Content-type'] = "multipart/mixed; boundary=\"Part-{$this->boundary['mixed']}\"";
			$this->message .= "--Part-{$this->boundary['mixed']}\r\n";
		}
		else if(!empty($this->files))
			$this->headers['Content-type'] = "multipart/related; boundary=\"Part-{$this->boundary['related']}\"";
		else if(!empty($this->versionhtml))
			$this->headers['Content-type'] = "multipart/alternative; boundary=\"Part-{$this->boundary['alternative']}\"";
		else
			$this->headers['Content-type'] = "text/plain; charset=\"us-ascii\"";

		if(!empty($this->files) && !empty($this->attachments))
			$this->message .= $this->wrapheader("Content-type: multipart/related; boundary=\"Part-{$this->boundary['related']}\"\r\n\r\n");

		if(!empty($this->files))
			$this->message .= "--Part-{$this->boundary['related']}\r\n";

		if(!empty($this->versionhtml) && (!empty($this->files) || !empty($this->attachments)))
			$this->message .= $this->wrapheader("Content-type: multipart/alternative; boundary=\"Part-{$this->boundary['alternative']}\"\r\n\r\n");

		if(!empty($this->versionhtml))
			$this->message .= "--Part-{$this->boundary['alternative']}\r\n";

		if(!empty($this->versionhtml) || !empty($this->files) || !empty($this->attachments)) {
			$this->message .= "Content-type: text/plain; charset=\"us-ascii\"\r\n";
			$this->message .= "Content-transfer-encoding: 7bit\r\n\r\n";
			$this->message .= $this->versionplain."\r\n\r\n";
		}
		else
			$this->message = $this->versionplain;

		if(!empty($this->versionhtml)) {
			$this->message .= "--Part-{$this->boundary['alternative']}\r\n";
			$this->message .= "Content-type: text/html; charset=\"{$this->charset}\"\r\n";
			$this->message .= "Content-transfer-encoding: quoted-printable\r\n\r\n";
			$this->message .= $this->versionhtml."\r\n\r\n";
			$this->message .= "--Part-{$this->boundary['alternative']}--\r\n";
		}

		if(!empty($this->files)) {
			$this->compileembedded();
			$this->message .= "--Part-{$this->boundary['related']}--\r\n";
		}

		if(!empty($this->attachments)) {
			$this->compileattachments();
			$this->message .= "--Part-{$this->boundary['mixed']}--\r\n";
		}

		$headers = array();
		foreach($this->headers as $k => $v) {
			if(is_array($v) && !empty($v))
				$headers[$k] = $v;
			else if($v != "" && !empty($v))
				$headers[$k] = wordwrap("$k: $v",75,"\r\n        ");
		}
		$this->headers = $headers;
	}

	function compileembedded() {
		foreach($this->files as $current) {
			$this->message .= "--Part-{$this->boundary['related']}\r\n";
			$this->message .= $this->wrapheader("Content-type: {$current['type']}; name=\"{$current['name']}\"\r\n");
			$this->message .= "Content-ID: <{$current['cid']}>\r\n";
			$this->message .= "Content-transfer-encoding: base64\r\n\r\n";
			$this->message .= "{$current['contents']}\r\n\r\n";
		}
	}

	function compileattachments() {
		foreach($this->attachments as $current) {
			$this->message .= "--Part-{$this->boundary['mixed']}\r\n";
			$this->message .= $this->wrapheader("Content-type: {$current['type']}; name=\"{$current['name']}\"\r\n");
			$this->message .= "Content-disposition: attachment; filename=\"{$current['name']}\"\r\n";
			$this->message .= "Content-transfer-encoding: base64\r\n\r\n";
			$this->message .= "{$current['contents']}\r\n\r\n";
		}
	}

	function sethtml($data) {
		$this->versionhtml = $this->toquotedprintable($this->parsehtml($data));
	}

	function setplain($data) {
		$this->versionplain = $this->to7bit($data);
	}

	function setheader($name,$data) {
		if(is_array($this->headers[$name]))
			$this->headers[$name][] = $data;
		else
			$this->headers[$name] = $data;
	}

	function setcharset($data) {
		$this->charset = $data;
	}

	function setsender($email,$name=null) {
		if(!$this->checkemail($email))
			return $this->error("$email is not a valid sender address.");
		$this->headers['From'] = "$name <$email>";
		if(empty($this->headers['Return-path']))
			$this->headers['Return-path'] = "$name <$email>";
	}

	function setreturn($email,$name=null) {
		if(!$this->checkemail($email))
			return $this->error("$email is not a valid return address.");
		$this->headers['Return-path'] = "$name <$email>";
	}

	function setsubject($data) {
		$this->headers['Subject'] = $data;
	}

	function addrecipient($email,$type) {
		$type = ucfirst($type);
		if(($type != "To" && $type != "Cc" && $type != "Bcc") || !$this->checkemail($email))
			return $this->error("$email is not a valid recipient.");
		$this->headers[$type][] = $email;
	}

	function setxmailer($data) {
		$this->headers['X-Mailer'] = $data;
	}

	function addattachment($filename,$data=null) {
		$file = array();
		if($data == null) {
			if($fp = @fopen($filename,"rb")) {
				$data = fread($fp,filesize($filename));
				@fclose($fp);
			}
			else
				$data = "";
		}
		$file['name'] = substr($filename,strstr($filename,"/")? strrpos($filename,"/")+1 : 0);
		$file['type'] = strstr($filename,".")? substr($filename,strrpos($filename,".")+1) : "";
		$file['contents'] = $this->tobase64($data);

		if(!empty($this->attachtypes[$file['type']]))
			$file['type'] = $this->attachtypes[$file['type']];
		else
			$file['type'] = "application/octet-stream";

		$this->attachments[] = $file;
	}

	function addembedded($data,$filename) {
		$file = array();
		$file['cid'] = md5(uniqid(microtime()));
		$file['name'] = substr($filename,strstr($filename,"/")? strrpos($filename,"/")+1 : 0);
		$file['type'] = $this->filetypes[substr($filename,strrpos($filename,".")+1)];
		$file['contents'] = $this->tobase64($data);

		$this->files[] = $file;

		return $file['cid'];
	}

	function parsehtml($data) {
		global $HTTP_SERVER_VARS;

		preg_match_all("/([\"\']{1}[^(\"|\')]+\.(".(implode("|",array_flip($this->filetypes))).")[\"\']{1})/Ui",$data,$filelist);

		$filelist = array_unique($filelist[0]);

		foreach($filelist as $current) {
			$current = substr($current,1,strlen($current)-2);
			if(preg_match("/^((http:\/\/)|\/)/",$current) && !empty($HTTP_SERVER_VARS['DOCUMENT_ROOT'])) {
				$temp = preg_replace("/^((http:\/\/([^\/])+\/)|\/){1}/Ui",$HTTP_SERVER_VARS['DOCUMENT_ROOT']."/",$current);
				if($fp = @fopen($temp,"rb")) {
					$filedata = fread($fp,filesize($temp));
					@fclose($fp);
				}
			}
			if(empty($filedata) && $fp = @fopen($current,"rb")) {
				$filedata = fread($fp,1048576);
				@fclose($fp);
			}
			if(empty($filedata))
				$filesrc = preg_replace("/^(\/){1}[^\/]+\//Ui","http://".$HTTP_SERVER_VARS['HTTP_HOST']."/",$current);
			else
				$filesrc = "cid:" . $this->addembedded($filedata,substr($current,strstr($current,"/")? strrpos($current,"/")+1 : 0));
			$data = str_replace($current,$filesrc,$data);
		}

		return $data;
	}

	function wrapheader($data) {
		return wordwrap($data,75,"\r\n        ",1);
	}

	function to7bit($data) {
		$data = str_replace("\r\n","\n",$data);
		$data = str_replace("\r","\n",$data);
		$data = str_replace("\n","\r\n",$data);
		return wordwrap($data,75,"\r\n",1);
	}

	function toquotedprintable($data) {
		for($whitespace = $encoded = "",$line = 0,$i = 0; $i < strlen($data); $i++) {
			$character = $data[$i];
			$order = ord($character);
			$encode = 0;
			switch($order) {
			case 9:
			case 32:
				$whitespace = $character;
				$character = "";
				break;
			case 10:
			case 13:
				$encoded .= $character;
				$line = 0;
				continue 2;
			default:
				if($order < 32 || $order > 127 || !strcmp($character,"="))
					$encode = 1;
				break;
			}
			if(strcmp($whitespace,"")) {
				if($line + 1 > 75) {
					$encoded .= "=\r\n";
					$line = 0;
				}
				$encoded .= $whitespace;
				$whitespace = "";
				$line++;
			}
			if(strcmp($character,"")) {
				if($encode) {
					$character = sprintf("=%02X",$order);
					$lengthencoded = 3;
				}
				else
					$lengthencoded = 1;
				if($line + $lengthencoded > 75) {
					$encoded .= "=\r\n";
					$line = 0;
				}
				$encoded .= $character;
				$line += $lengthencoded;
			}
		}

		return $encoded;
	}

	function tobase64($data) {
		return wordwrap(base64_encode($data),76,"\r\n",1);
	}

	function checkemail($data) {
		if(preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[_a-z0-9-]+)*$/i",$data))
			return 1;
		return 0;
	}

	function error($error) {
		$this->errors[] = $error;
		return 0;
	}
	
	function geterrors() {
		return $this->errors;
	}
}

class smtpmail {
	var $hostname	= "";
	var $hostaddr	= "";
	var $smtpport	= 25;
	var $errors		= array();

	function smtpmail($hostname,$smtpport=25) {
		$this->hostname = $hostname;
		$this->hostaddr = gethostbyname($hostname);
		$this->smtpport = $smtpport;
	}

	function sendmail($headers,$message) {
		fputs($this->server,"MAIL FROM:".(substr($headers['Return-path'],strrpos($headers['Return-path'],"<")+1,strlen($headers['Return-path'])-strrpos($headers['Return-path'],"<")-2))."\r\n");

		$response = fgets($this->server,1024);
		if(substr($response,0,3) != "250")
			return $this->error("Invalid from address.");

		$first = 1;

		if(!empty($headers['To']))
		foreach($headers['To'] as $k => $v) {
			fputs($this->server,"RCPT TO:$v\r\n");

			if(!empty($first)) {
				fgets($this->server,1024);
				unset($first);
			}
			$response = fgets($this->server,1024);
			if(substr($response,0,3) != "250" && substr($response,0,3) != "251")
				unset($headers['To'][$k]);
		}

		if(!empty($headers['Cc']))
		foreach($headers['Cc'] as $k => $v) {
			fputs($this->server,"RCPT TO:$v\r\n");

			if(!empty($first)) {
				fgets($this->server,1024);
				unset($first);
			}
			$response = fgets($this->server,1024);
			if(substr($response,0,3) != "250" && substr($response,0,3) != "251")
				unset($headers['Cc'][$k]);
		}

		if(!empty($headers['Bcc']))
		foreach($headers['Bcc'] as $k => $v) {
			fputs($this->server,"RCPT TO:$v\r\n");

			if(!empty($first)) {
				fgets($this->server,1024);
				unset($first);
			}
			$response = fgets($this->server,1024);
			if(substr($response,0,3) != "250" && substr($response,0,3) != "251")
				unset($headers['Bcc'][$k]);
		}

		if(empty($headers['To']) && empty($headers['Cc']) && empty($headers['Bcc'])) {
			fputs($this->server,"RSET\r\n");
			return $this->error("No recipients specified.");
		}
		else {
			if(!empty($headers['To']))
				$headers['To'] = "To: ".implode(", ",$headers['To']);
			if(!empty($headers['Cc']))
				$headers['Cc'] = "Cc: ".implode(", ",$headers['Cc']);
			if(!empty($headers['Bcc']))
				unset($headers['Bcc']);
		}

		fputs($this->server,"DATA\r\n");
		$response = fgets($this->server,1024);
		if(substr($response,0,3) != "354")
			return $this->error("Cannot send data.");

		$returnpath = $headers['Return-path'];
		unset($headers['Return-path']);
		$message = str_replace("\r\n.\r\n","\r\n..\r\n",$message);
		$headers = implode("\r\n",$headers);

		fputs($this->server,$headers."\r\n".$message."\r\n.\r\n");
		$response = fgets($this->server,1024);
		if(substr($response,0,3) != "250")
			return $this->error("Cannot send data.");
	}

	function openconnection() {
		if(!$this->server = @fsockopen($this->hostaddr,$this->smtpport))
			return $this->error("Could not connect to SMTP server.");

		fputs($this->server,"HELO {$this->hostname}\r\n");

		$response = fgets($this->server,1024);
		if(substr($response,0,3) != "220")
			return $this->error("Could not connect to SMTP server.");
	}

	function closeconnection() {
		fputs($this->server,"QUIT\r\n");

		$response = fgets($this->server,1024);
		if(substr($response,0,3) != "221")
			return $this->error("Could not close connection to SMTP server.");

		@fclose($this->server);
	}

	function error($error) {
		$this->errors[] = $error;
		return 0;
	}
	
	function geterrors() {
		return $this->errors;
	}
} ?>