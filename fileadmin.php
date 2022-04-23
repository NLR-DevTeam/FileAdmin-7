<?php $PASSWORD="TYPE-YOUR-PASSWORD-HERE"; $VERSION=6.077;

	/* SimSoft FileAdmin	   © SimSoft, All rights reserved. */
	/*请勿将包含此处的截图发给他人，否则其将可以登录FileAdmin！*/
	error_reporting(0);
	function scandirAll($dir,$first=false){	
		$files = [];
		$child_dirs = scandir($dir);
		foreach($child_dirs as $child_dir){if($child_dir != '.' && $child_dir != '..'){
			if(is_dir($dir."/".$child_dir)){$files=array_merge($files,scandirAll($dir."/".$child_dir));}
			else{array_push($files,$dir."/".$child_dir);}
		}}
		return $files;
	}
	function create_zip($files=array(),$destination='',$overwrite=false){
		if(file_exists($destination)&&!$overwrite){return false;}
		$valid_files=array();
		if(is_array($files)){foreach($files as $file){if(file_exists($file)&&!is_dir($file)){$valid_files[]=$file;}}}
		if(count($valid_files)) {
			$zip = new ZipArchive();
			if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true){return false;}
			foreach($valid_files as $file){$zip->addFile($file,$file);}
			$zip->close();
			return file_exists($destination);
		}else{return false;}
	}
	function unzip_file(string $zipName,string $dest){
		if(!is_file($zipName)){return '1003';}
		if(!is_dir($dest)){return '1002';}
		else{
			$zip=new ZipArchive();
			if($zip->open($zipName)){
				$zip->extractTo($dest);
				$zip->close();
				return '200';
			}else{return '1001';}
		}
	}
	function unlinkDir($dir){
		$files=scandir($dir);
		foreach ($files as $key => $filename) {
			if($filename!="."&&$filename!=".."){
				if(is_dir($dir."/".$filename)){unlinkDir($dir."/".$filename);}else{unlink($dir."/".$filename);}
			}
		}
		rmdir($dir);
	}
	function nbMkdir($pathname){
		$paths = explode("/", $pathname);
		$nowp = "";
		foreach($paths as $key=>$value) {
			$nowp .= $value . "/";
			if ($value == "." || $value == ".." || $value == "") continue;
			mkdir($nowp);
		}
	}
	function copyDir($from,$to){
		if(!is_dir($to)){nbMkdir($to);}
		echo $from."|md|".$to.PHP_EOL;
		$currDir=$from;
		$currFiles=scandir($currDir);
		foreach($currFiles as $filename){
			if($filename!="."&&$filename!=".."){
				$trueFileName=$currDir.$filename;
				if(is_dir($trueFileName)){copyDir($trueFileName.'/',$to.$filename.'/');}
				else{copy($trueFileName,$to.$filename);}
			}
		}
	}
   function dirsize($dir){
		@$dh=opendir($dir);$size=0;
		while($file = @readdir($dh)){
			if($file!="." && $file!=".."){
				$path = $dir."/".$file;
				if (is_dir($path)){$size+=dirsize($path);}elseif(is_file($path)){$size += filesize($path);}
			}
		}
		@closedir($dh);return $size;
	}
	
	$ACT=$_POST["a"];$PWD=$_POST["pwd"];
	if($ACT){
		if($ACT=="login"){
			if($_POST["loginPwd"]==$PASSWORD){echo "200||".password_hash($PASSWORD.date("Ymd"),PASSWORD_DEFAULT);}else{echo "1001";}
		}elseif(password_verify($PASSWORD.date("Ymd"),$PWD)){
			if($ACT=="check"){
				echo "200";
			}elseif($ACT=="files"){
				if(strstr($_POST["name"],"./")){
					echo "1002";
				}elseif(is_dir(".".$_POST["name"])){
					$fileArray=scandir(".".$_POST["name"]);
					$fileArrayModified=[];
					foreach($fileArray as $filename){
						$fileisdir=is_dir(".".$_POST["name"].$filename);
						if($fileisdir){
							$filesize=0;array_push($fileArrayModified,array("name"=>$filename,"dir"=>$fileisdir,"size"=>$filesize));
						}
					}
					foreach($fileArray as $filename){
						$fileisdir=is_dir(".".$_POST["name"].$filename);
						if(!$fileisdir){
							$filesize=filesize(".".$_POST["name"].$filename)/1024;
							array_push($fileArrayModified,array("name"=>$filename,"dir"=>$fileisdir,"size"=>$filesize));
						}
					}
					echo "200||".rawurlencode(json_encode($fileArrayModified));
				}else{
					echo "1001";
				}
			}elseif($ACT=="getfile"){
				if(file_exists(".".$_POST["name"].".fajs")){echo file_get_contents(".".$_POST["name"].".fajs");}else{echo file_get_contents(".".$_POST["name"]);}
			}elseif($ACT=="save"){
				file_put_contents(".".$_POST["name"],$_POST["data"]);
				if(file_exists(".".$_POST["name"].".fajs")){unlink(".".$_POST["name"].".fajs");}
				echo "200";
			}elseif($ACT=="fajssave"){
				file_put_contents(".".$_POST["name"],$_POST["obfuscate"]);
				file_put_contents(".".$_POST["name"].".fajs",$_POST["original"]);
				echo "200";
			}elseif($ACT=="zip"){
				$zipResult=create_zip(scandirAll(realpath(".".$_POST["name"]),true),".".$_POST["name"]."FileAdmin_".time().".zip",false);
				if($zipResult){echo "200";}else{echo "1001";}
			}elseif($ACT=="unzip"){
				echo unzip_file(".".$_POST["name"],".".$_POST["dir"],false);
			}elseif($ACT=="mkdir"){
				mkdir(".".$_POST["name"]);
				echo "200";
			}elseif($ACT=="rename"){
				if(!file_exists(".".$_POST["dir"].$_POST["new"])){
					rename(".".$_POST["dir"].$_POST["old"],".".$_POST["dir"].$_POST["new"]);
					echo "200";
				}else{
					echo "1002";
				}
			}elseif($ACT=="del"){
				$delFiles=json_decode(rawurldecode($_POST["files"]));
				foreach($delFiles as $filename){
					$trueFileName=".".$_POST["dir"].$filename;
					if(is_dir($trueFileName)){unlinkDir($trueFileName);}else{unlink($trueFileName);}
					echo "200";
				}
			}elseif($ACT=="chkupd"){
				$latest=file_get_contents("https://api.simsoft.top/fileadmin/latest/?stamp=".time());
				if($latest && $latest!=$VERSION){
					$updinfo=file_get_contents("https://api.simsoft.top/fileadmin/updateinfo/?stamp=".time());
					if($updinfo){
						echo $updinfo;
					}else{echo "1002";}
				}else{echo "1001";}
			}elseif($ACT=="applyversion"){
				$updater=file_get_contents("https://api.simsoft.top/fileadmin/updater/?stamp=".time());
				if($updater){
					file_put_contents("./FileAdminUpdater.php",$updater);
					header("location: ./FileAdminUpdater.php?famain=".end(explode("/",$_SERVER['PHP_SELF'])));
				}else{echo "1001";}
			}elseif($ACT=="copy"){
				$operateFiles=json_decode(rawurldecode($_POST["files"]));
				foreach($operateFiles as $filename){
					$fromfile=".".$_POST["from"].$filename;
					$tofile=".".$_POST["to"].$filename;
					if(is_dir($fromfile)){copyDir($fromfile.'/',".".$_POST["to"].$filename."/");}else{copy($fromfile,$tofile);}
				}
			}elseif($ACT=="move"){
				$operateFiles=json_decode(rawurldecode($_POST["files"]));
				foreach($operateFiles as $filename){
					$fromfile=".".$_POST["from"].$filename;
					$tofile=".".$_POST["to"].$filename;
					rename($fromfile,$tofile);
				}
			}elseif($ACT=="find_by_content"){
				$trueDirName=".".implode("/",explode("/",$_POST["dir"]));
				$filelist=scandirAll($trueDirName);
				$searchedFiles=[];
				$textFiles=explode(" ",$_POST["type"]);
				foreach($filelist as $filenameFound){
					if($_POST["type"]=='' || in_array(strtolower(end(explode(".",$filenameFound))),$textFiles)){
						$filedata=file_get_contents($filenameFound);
						if($_POST["case"]=="1"){$fileInNeed=strstr($filedata,$_POST["find"]);}else{$fileInNeed=stristr($filedata,$_POST["find"]);}
						if($fileInNeed){array_push($searchedFiles,str_replace("./","/",$filenameFound));}
					}
				}
				echo "200||".rawurlencode(json_encode($searchedFiles));
			}elseif($ACT=="find_by_name"){
				$trueDirName=".".implode("/",explode("/",$_POST["dir"]));
				$filelist=scandirAll($trueDirName);
				$textFiles=explode(" ",$_POST["type"]);
				$searchedFiles=[];
				foreach($filelist as $filenameFound){
					if($_POST["type"]=='' || in_array(strtolower(end(explode(".",$filenameFound))),$textFiles)){
						if($_POST["case"]=="1"){$fileInNeed=strstr($filenameFound,$_POST["find"]);}else{$fileInNeed=stristr($filenameFound,$_POST["find"]);}
						if($fileInNeed){array_push($searchedFiles,str_replace("./","/",$filenameFound));}
					}
				}
				echo "200||".rawurlencode(json_encode($searchedFiles));
			}elseif($ACT=="replace"){
				$trueDirName=".".implode("/",explode("/",$_POST["dir"]));
				$filelist=scandirAll($trueDirName);
				$replaceCount=0;
				$textFiles=explode(" ",$_POST["type"]);
				foreach($filelist as $filenameFound){
					if($_POST["type"]=='' || in_array(strtolower(end(explode(".",$filenameFound))),$textFiles)){
						$filedata=file_get_contents($filenameFound);
						$fileInNeed=strstr($filedata,$_POST["find"]);
						if($fileInNeed){
							$replaceCount++;
							$newFiledata=str_replace($_POST["find"],$_POST["replace"],$filedata);
							file_put_contents($filenameFound,$newFiledata);
						}
					}
				}
				echo "200||".$replaceCount;
			}elseif($ACT=="space"){
				if(is_dir(".".$_POST["name"])){
					$total=disk_total_space(".".$_POST["name"]);
					$free=disk_free_space(".".$_POST["name"]);
					$used=$total-$free;
					$current=dirsize(".".$_POST["name"]);
					echo "200||".$total."||".$free."||".$used."||".$current;
				}else{echo "1001";}
			}
		}else{echo "1000";}
	}elseif(password_verify($PASSWORD.date("Ymd"),$_GET["pwd"]) && $_GET["a"]=="down"){
		header("Content-Disposition: attachment;filename=".rawurlencode(end(explode("/",$_GET["name"]))));
		echo file_get_contents(".".$_GET["name"]);
	}elseif(password_verify($PASSWORD.date("Ymd"),$_GET["pwd"]) && $_GET["a"]=="upload"){
		$destDir=".".$_GET["dir"];
		if(!is_dir($destDir)){nbMkdir($destDir);}
		move_uploaded_file($_FILES["file"]["tmp_name"],$destDir.$_FILES["file"]["name"]);
	}elseif($_GET["a"]=="ver"){
		$latest=file_get_contents("https://api.simsoft.top/fileadmin/latest/?stamp=".time());
		if($latest && $latest!=$VERSION){echo "1001";}else{echo "v".$VERSION;}
	}elseif($_GET["a"]=="css"){ 
		header("content-type: text/css");
?>/*<style>*/
/* FileAdmin CSS */
#passwordManagerUsername{display:none}
*{box-sizing:border-box;}
body{margin:0;user-select:none;margin-top:45px;font-family:微软雅黑;background:#f5f5f5;min-height:100%;}
::-webkit-scrollbar{display:none;}
.title{position:fixed;top:0;left:0;right:0;height:fit-content;box-shadow:0 0 5px 0 rgba(0,0,0,.4);height:40px;background:white;z-index:5;vertical-align:top;}
.appName{font-size:1.5em;position:absolute;top:0;height:fit-content;bottom:0;left:10px;margin:auto}
.appName b{color:#1e9fff;}
#versionNote{border-radius:10px 10px 10px 0;background:#f5f5f5;display:inline-block;margin-left:5px;color:#ababab;padding:0 5px;font-size:.4em;vertical-align:top}
#versionNote.active{background:#1e9fff;color:white}
.title svg{position:absolute;top:0;bottom:0;right:10px;margin:auto;transform:rotate(180deg)}
.module{display:none;background:white;}
.module.shown{display:block;animation:showModule .3s ease;}
.loading, .texteditor.shown{animation:none!important;}
@keyframes showModule{from{transform:translateY(15px);opacity:0;}to{transform:none;opacity:1;}}
.login{text-align:center;position:fixed;inset:0;margin:auto;padding:10px;height:fit-content;width:fit-content;background:white;border-radius:5px;}
.loginTitle{font-size:1.7em;margin-bottom:10px;}
#loginPassword{vertical-align:middle;height:35px;border-radius:5px 0 0 5px;border:0;outline:none;padding:5px;border:1px solid rgba(0,0,0,.1);border-right:0;transition:border .2s;}
#loginPassword:focus{border:1px solid #1e9fff;border-right:0;}
.loginBtn{transition:all .2s;height:35px;width:35px;vertical-align:middle;outline:none;border:0;border-radius:0 5px 5px 0;background:#1e9fff;color:white;font-size:1.2em;}
.loginBtn:hover{background:#0092ff;}
.loginBtn:active{color:#bae2ff;}
.addressBar{margin-top:5px;border-radius:5px;background:white;overflow:hidden;display:inline-block;text-align:left;max-width:500px;width:100%}
.addressBar button{font-weight:bold;width:30px;height:32px;border:0;outline:0;background:transparent;border-right:1px solid #f5f5f5;vertical-align:middle;}
.addressBar button:hover{background:rgba(0,0,0,.09);}
.addressBar button:active{background:rgba(0,0,0,.12);}
.addressBar div{vertical-align:middle;display:inline-block;width:calc(100% - 60px);padding:0 10px;overflow-x:scroll;white-space:nowrap}
.files,.search{margin:10px;background:transparent;text-align:center;}
#fileList,#searchOptnArea,#searchResult{margin-top:5px;border-radius:5px;background:white;overflow:hidden;margin-bottom:10px;display:inline-block;text-align:left;max-width:500px;width:100%}
#searchOptnArea{margin-bottom:0;}
#fileList center{padding:30px 0;opacity:.6}
#fileList .file,#searchResult .file{padding:10px;text-align:center;}
#fileList .file:hover,#searchResult .file:hover{background:rgba(0,0,0,.09);}
#fileList .file:active,#searchResult .file:active{background:rgba(0,0,0,.12)}
.file .fileIco{display:inline-block;margin-right:5px;width:23px;height:23px;vertical-align:middle}
.file.selected[data-isdir^=true] .fileIco{fill:black;}
.file.selected .fileIco{filter:invert(1)}
#fileList .file .fileName,#searchResult .fileName{display:inline-block;width:calc(100% - 135px);text-align:left;vertical-align:middle;font-size:1.1em;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
#searchResult .fileName{width:calc(100% - 40px);}
#fileList .file .size{display:inline-block;width:90px;text-align:right;vertical-align:middle;opacity:.5;}
#fileList .file[data-isdir^=true] .size{opacity:0;}
#fileList .file.selected{background:#1e9fff;color:white;}
.texteditor{margin:10px;}
#textEditor{border-radius:5px;position:absolute;top:50px;left:10px;right:10px;height:calc(100% - 60px);border:1px solid rgba(0,0,0,.1);overflow:hidden;}
#textEditor *::-webkit-scrollbar{display:block;width:3px;height:0px;background:#ebebeb;}
#textEditor *::-webkit-scrollbar:hover{width:15px}
#textEditor *::-webkit-scrollbar-thumb{border-radius:2px;background:#bababa;}
contextmenukey{display:none;}
contextmenu{z-index:30;position:fixed;border:1px solid #c1c1c1;width:150px;height:fit-content;background:white;overflow:hidden;box-shadow:1px 1px 2px 0 rgba(0,0,0,.2);}
contextmenu button{outline:none;display:block;border:0;padding:5px 10px;background:white;width:100%;text-align:left;position:relative;}
contextmenu button:hover{background:rgba(0,0,0,.05);}
contextmenu button:active{background:rgba(0,0,0,.1);}
contextmenu button contextmenukey{position:absolute;right:10px;top:0;bottom:0;height:fit-content;margin:auto;display:inline-block;opacity:.5;}
.imgviewer,.vidviewer{background:transparent;}
#imgviewer{width:calc(100% - 10px);height:calc(100vh - 100px);background:white;margin:5px;border:1px solid rgba(0,0,0,.1);border-radius:5px;object-fit:contain;}
#vidviewer{width:calc(100% - 10px);height:calc(100vh - 100px);background:black;margin:5px;border:1px solid rgba(0,0,0,.1);border-radius:5px;outline:none;}
.updinfo{margin:10px;padding:10px;}
#updinfo{padding:10px;}
.upload{inset:0;margin:auto;height:fit-content;width:340px;padding:10px;border-radius:5px;position:fixed;overflow:hidden;}
.uploadProgress{height:8px;border-radius:4px;background:#f0f0f0;overflow:hidden;margin:10px 0;}
.uploadText{width:100%;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#uploadProgressBar{height:8px;transition:width .2s;background:#1e9fff;width:0;}
.loadingAnimation{position:fixed;inset:0;margin:auto;width:fit-content;height:fit-content;z-index:20}
.loadingAnimationDot{animation:loadingDot .8s linear 0s infinite;font-weight:bold;font-size:2em;display:inline-block;opacity:.1;}
#dot2{animation-delay:.1s!important}
#dot3{animation-delay:.2s!important}
#searchAddrBar{padding:5px;overflow-x:scroll;white-space:nowrap}
#searchOptnArea div span{width:100px;display:inline-block;vertical-align:middle;padding:5px;}
#searchOptnArea div input,#searchOptnArea div select{background:white;padding:3px;padding-left:0;display:inline-block;vertical-align:middle;width:calc(100% - 105px);border:0;border-bottom:1px solid #f5f5f5;outline:none;}
#searchOptnArea div input{padding-left:5px;}
#mobileFastInput{position:fixed;top:-90px;bottom:calc(100% - 40px);height:fit-content;background:white;text-align:center;z-index:10;transition:top .2s;width:100vw;margin:auto;padding:5px 0;}
.mobileInputBtn{display:inline-block;width:calc(100% / 14 - 5px);border-radius:5px;padding:5px 2px;}
.mobileInputBtn:active{background:#eeeeee;}
@keyframes loadingDot{
	0%{transform:translateY(0px)}
	15%{transform:translateY(10px)}
	30%{transform:translateY(-10px)}
	45%{transform:translateY(5px)}
	60%{transform:translateY(5px)}
	75%{transform:translateY(0)}
}
@media screen and (min-width:700px) {
	.menu{top:-30px;transition:top .2s;position:fixed;z-index:20;right:40px;left:150px;height:24px;text-align:right;}
	.menu button{outline:none;border:0;background:#f5f5f5;height:100%;width:45px;border-radius:5px;}
	.menu button.big{width:70px}
	.menu button:hover{background:#f9f9f9}
	.menu button:active{background:#f0f0f0}
	.menu.shown{top:8px;}
	#loadingText{position:fixed;top:0;left:140px;bottom:calc(100% - 40px);margin:auto;z-index:20;height:fit-content;opacity:.5;font-size:.9em;}
}
@media screen and (max-width:700px) {
	body{margin-bottom:50px;}
	.menu{bottom:-35px;transition:bottom .2s;box-shadow:0 0 5px 0 rgba(0,0,0,.4);background:white;position:fixed;z-index:10;right:0;left:0;height:30px;text-align:center;overflow-y:scroll;white-space:nowrap}
	.menu button{outline:none;border:0;height:100%;width:fit-content;background:transparent;width:30px;padding:0;}
	.menu button.big{width:60px}
	.menu.shown{bottom:0;}
	#textEditor{height:calc(100% - 90px)}
	#loadingText{position:fixed;top:0;right:50px;bottom:calc(100% - 40px);margin:auto;z-index:20;height:fit-content;opacity:.5;font-size:.9em;}
}
/*</style>*/<?php }elseif($_GET["a"]=="js"){header("content-type: text/javascript"); ?>//<script>
/* FileAdmin Javascript */
//=========================================初始化
	window.onload=function(){
		forwardFromConfirm=false;fileHoverSelecting=false;dirOperating="/";uploadNotFinished=false;request("check",null,function(){loadFileList(dirOperating,true);history.replaceState({"mode":"fileList","dir":"/"},document.title)});
		if(navigator.userAgent.indexOf("Chrome")==-1){alert("FileAdmin 目前仅兼容 Google Chrome 和 Microsoft Edge 的最新版本，使用其他浏览器访问可能导致未知错误。")}
		document.getElementById("passwordManagerUsername").value="FileAdmin（"+location.host+"）";
		moveOrCopyMode=null;
		fetch("?a=ver").then(function(d){return d.text()}).then(function(d){
			if(d=="1001"){document.getElementById("versionNote").innerText="点击更新";document.getElementById("versionNote").classList.add("active")}else{document.getElementById("versionNote").innerText=d;}
		}).catch(function(err){document.getElementById("versionNote").innerText="出错"})
		window.onpopstate=function(){
			if(!forwardFromConfirm){
				if(document.querySelector(".texteditor.shown")){if(textEditor.getValue()!=lastSaveContent && !confirm("您有内容还没有保存哦，确实要退出嘛？")){forwardFromConfirm=true;history.forward();return;}}
				if(document.querySelector(".upload.shown")&&uploadNotFinished){history.forward()}else{
					let state=event.state;
					if(state.mode){
						let mode=state.mode;
						if(mode=="fileList"){dirOperating=state.dir;loadFileList(dirOperating,true)}else{
							history.back();
						}
					}
				}
			}else{forwardFromConfirm=false;}
		}
	}
	window.onkeydown=function(){
		if(event.keyCode==191){
			if(document.querySelector(".files.shown")){editAddressBar();}
			if(document.querySelector(".login.shown")){event.preventDefault();document.getElementById("loginPassword").focus();}
		}else if(event.ctrlKey==true&&event.keyCode==83){
			event.preventDefault();
			if(document.querySelector(".texteditor.shown")){saveFile();}
		}else if(event.keyCode==27){
			if(document.querySelector(".texteditor.shown")){history.back();}
			else if(document.querySelector(".files.shown")){previousDir();}
		}else if(event.ctrlKey==true&&event.keyCode==65){
			if(document.querySelector(".files.shown")){event.preventDefault();fileSelected=fileListOperating;loadFileSelected();}
		}else if(event.keyCode==46){
			if(document.querySelector(".files.shown")){delFile();}
		}else if(event.ctrlKey==true&&event.keyCode==67){
			if(document.querySelector(".files.shown")){setCopyFiles();}
		}else if(event.ctrlKey==true&&event.keyCode==88){
			if(document.querySelector(".files.shown")){setMoveFiles();}
		}else if(event.ctrlKey==true&&event.keyCode==86){
			if(document.querySelector(".files.shown")){filePaste();}
		}else if(event.keyCode==116){
			event.preventDefault();
			if(document.querySelector(".files.shown")){loadFileList(dirOperating,true);}
			if(document.querySelector(".texteditor.shown")){reloadEditor()}
		}
	}
//=========================================公共函数
	function request(act,txt,callback){
		if(txt){fetchBody="a="+act+"&pwd="+encodeURIComponent(localStorage.getItem("FileAdmin_Password"))+"&"+txt;}
		else{fetchBody="a="+act+"&pwd="+encodeURIComponent(localStorage.getItem("FileAdmin_Password"));}
		fetch('?stamp='+new Date().getTime(),{
			body:fetchBody,
			method:"POST",
			headers:{'Content-Type':'application/x-www-form-urlencoded'}
		})
		.then(res=>res.text())
		.then(txt=>{
			let parsed=txt.split("||");
			let code=Number(parsed[0]);
			if(code==1000){showModule("login");}else{
				if(parsed[1]){msg=parsed[1];}else{msg=null;}
				if(callback){callback(code,msg,txt);}
			}
		})
		.catch(err=>{alert(err);})
	}
	function showModule(name){
		document.title="FileAdmin | 极致文件管理体验";
		hideMenu();
		if(document.querySelector(".module.shown")){document.querySelector(".module.shown").classList.remove("shown");}
		document.querySelector(".module[data-module^='"+name+"']").classList.remove("hidden");
		document.querySelector(".module[data-module^='"+name+"']").classList.add("shown");
		if(name=="login"){document.getElementById("logoutBtn").style.display="none";}else{document.getElementById("logoutBtn").style.display="block";}
		if(name!="login"&&name!="files"&&name!="loading"){history.pushState({'mode':'other'},document.title)}
		if(name!="texteditor"&&name!="loading"){document.getElementById("mobileFastInput").style="";}
	}
	function showMenu(name){
		if(document.querySelector(".menu.shown")){document.querySelector(".menu.shown").classList.remove("shown");}
		document.querySelector(".menu[data-menu^='"+name+"']").classList.add("shown");
	}
	function hideMenu(){
		if(document.querySelector(".menu.shown")){document.querySelector(".menu.shown").classList.remove("shown");}
	}
	function humanSize(num){
		bytes=num/102.4;
		if(bytes==0){return "0.00B";} 
		var e=Math.floor(Math.log(bytes)/Math.log(1024)); 
		return(bytes/Math.pow(1024, e)).toFixed(2)+'KMGTP'.charAt(e)+'B'; 
	}
//=========================================登录
	function loginCheckEnter(eve){if(eve.keyCode==13){login()}}
	function login(){
		showModule("loading");
		request("login","loginPwd="+document.getElementById("loginPassword").value,function(code,msg){
			if(code==200){
				localStorage.setItem("FileAdmin_Password",msg);
				loadFileList(dirOperating,true);
				history.replaceState({"mode":"fileList","dir":"/"},document.title)
			}else{
				showModule("login");
				alert("密码输入错误 (⊙x⊙;)");
			}
		})
	}
//========================================上传文件
	function addFilesToUploads(ele){
		waitingToUpload=[];
		waitingToUploadCount=0;
		Array.from(ele.files).forEach(addFileToUploadArr);
		showModule("upload");
		uploadFileFromList(0);
		ele.value='';uploadNotFinished=true;
	}
	document.addEventListener('paste',function(event){
		if(document.querySelector(".files.shown") && !moveOrCopyMode){
			var items=event.clipboardData && event.clipboardData.items;
			if(items && items.length){
				waitingToUpload=[];
				waitingToUploadCount=0;
				for(var i = 0; i < items.length; i++){if(items[i].type!==''){if(items[i].getAsFile()){addFileToUploadArr(items[i].getAsFile());}}}
				showModule("upload");
				uploadNotFinished=true;
				uploadFileFromList(0);
			}
		}
	});
	function addFileToUploadArr(file){
		waitingToUpload.push({"file":file,"dir":dirOperating});
		waitingToUploadCount++;
	}
	function addDirToUploads(ele){
		waitingToUpload=[];
		waitingToUploadCount=0;
		Array.from(ele.files).forEach(addDirToUploadArr);
		showModule("upload");
		uploadFileFromList(0);
		ele.value='';
	}
	function addDirToUploadArr(file){
		let relativeDir=file.webkitRelativePath.split("/").slice(0,file.webkitRelativePath.split("/").length-1).join("/")+"/";
		waitingToUpload.push({"file":file,"dir":dirOperating+relativeDir});
		waitingToUploadCount++;
	}
	function uploadFileFromList(id){
		lastUploadTime=new Date().getTime();
		lastUploadProgress=0;
		if(!waitingToUpload[id]){uploadNotFinished=false;history.back();}else{
			waitingToUploadCount--;
			document.getElementById("uploadText-CurrFile").innerText=waitingToUpload[id]["file"]["name"];
			document.getElementById("uploadText-Waiting").innerText=waitingToUploadCount;
			document.getElementById("uploadText-DestDir").innerText=waitingToUpload[id]["dir"];
			document.getElementById("uploadProgressBar").style.display="none";
			setTimeout(function(){document.getElementById("uploadProgressBar").style.width="0%";document.getElementById("uploadProgressBar").style.display="block";},50)
			document.getElementById("uploadText-CurrProg").innerText="0% (正在连接...)"
			xhr=new XMLHttpRequest();
			xhr.onload=function(){id++;uploadFileFromList(id)};
			xhr.open("POST","?a=upload&pwd="+encodeURIComponent(localStorage.getItem("FileAdmin_Password"))+"&dir="+encodeURIComponent(waitingToUpload[id]["dir"]),true);
			xhr.setRequestHeader("X-Requested-With","XMLHttpRequest");
			var fd=new FormData();
			fd.append("file",waitingToUpload[id]["file"]);
			xhr.upload.onprogress=function(eve){
				loaded=eve.loaded/eve.total;
				percent=Math.round((loaded * 100))+"%"
				document.getElementById("uploadProgressBar").style.width=percent;
				document.getElementById("uploadText-CurrProg").innerText=percent+" ("+humanSize(eve.loaded/10)+" / "+humanSize(eve.total/10)+")";
				uploadSpeed=humanSize((eve.loaded-lastUploadProgress)/(new Date().getTime()-lastUploadTime)*100)+"/S"
				document.getElementById("uploadText-CurrSpeed").innerText=uploadSpeed;
				if(percent=="100%"){document.getElementById("uploadText-CurrProg").innerText=percent+" (正在处理...)";}
				lastUploadTime=new Date().getTime();
				lastUploadProgress=eve.loaded;
			}
			xhr.send(fd);
		}
	}
//========================================文件管理器
	function loadFileList(dir,fromState){
		fileSelected=[];
		document.getElementById("addressBar").innerText="根目录"+dir.replaceAll("/"," / ");
		showModule("loading");
		request("files","name="+dir,function(code,data){
			if(code==200){
				fileListArr=JSON.parse(decodeURIComponent(data));
				fileListOperating=[];
				fileListHtml="";
				fileListArr.forEach(addToFileListHtml);
				document.getElementById("fileList").innerHTML=fileListHtml;
				if(fileListHtml==""){
					document.getElementById("fileList").innerHTML="<center>请求的目录为空 ヽ(*。>Д<)o゜</center>"
				}
			}else if(code=="1001"){document.getElementById("fileList").innerHTML="<center>请求的目录不存在捏 (ノへ￣、)</center>"}
			else if(code="1002"){document.getElementById("fileList").innerHTML="<center>目录名称格式有误 (ﾟДﾟ*)ﾉ</center>"}
			showModule("files");
			showMenu("files-noselect");
		})
		if(!fromState){history.pushState({"mode":"fileList","dir":dir},document.title)}
		if(window.offsetBeforeEditing){setTimeout(function(){scrollTo(0,offsetBeforeEditing);offsetBeforeEditing=null;},580);}
	}
	function addToFileListHtml(data){
		if(data.name!="."&&data.name!=".."){
			fileType=data.name.split(".")[data.name.split(".").length-1].toLowerCase();
			fileListOperating.push(data.name);
			fileListHtml=fileListHtml+`<div class="file" onmouseover="hoverSelect(this)" data-isdir=`+data.dir+` data-filename="`+data.name+`" onclick="viewFile(this)" oncontextmenu="fileContextMenu(this)">
				`+getFileIco(fileType,data.dir)+`
				<div class="fileName">`+data.name+`</div>
				<div class="size">`+humanSize(data.size*102.4)+`</div>
			</div>`;
		}
	}
	function getFileIco(type,dir){
		if(dir){return `<svg style='padding:2px' viewBox="0 0 16 16" version="1.1" class="fileIco" fill="#1e9fff"><path d="M1.75 1A1.75 1.75 0 000 2.75v10.5C0 14.216.784 15 1.75 15h12.5A1.75 1.75 0 0016 13.25v-8.5A1.75 1.75 0 0014.25 3H7.5a.25.25 0 01-.2-.1l-.9-1.2C6.07 1.26 5.55 1 5 1H1.75z"></path></svg>`;}
		else{if(type=="fajs"){return `<svg class="fileIco" viewBox="0 0 48 48" fill="none"><path d="M10 44H38C39.1046 44 40 43.1046 40 42V14L31 4H10C8.89543 4 8 4.89543 8 6V42C8 43.1046 8.89543 44 10 44Z" stroke="#000" stroke-width="3"" stroke-linecap="round" stroke-linejoin="round"/><rect x="17" y="27" width="14" height="8" fill="none" stroke="#000" stroke-width="3"" stroke-linecap="round" stroke-linejoin="round"/><path d="M28 27V23C28 21.3431 27 19 24 19C21 19 20 21.3431 20 23V27" stroke="#000" stroke-width="3"" stroke-linecap="round" stroke-linejoin="round"/><path d="M30 4V14H40" stroke="#000" stroke-width="3"" stroke-linecap="round" stroke-linejoin="round"/></svg>`;}
		else{return `<svg class="fileIco" viewBox="0 0 48 48" fill="none"><path d="M10 44H38C39.1046 44 40 43.1046 40 42V14L31 4H10C8.89543 4 8 4.89543 8 6V42C8 43.1046 8.89543 44 10 44Z" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M30 4V14H40" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>`}}
	}
	function editAddressBar(){
		let newDir=prompt("请输入想转到的路径 (o゜▽゜)o☆",dirOperating);
		if(newDir){
			if(newDir.split("")[0]!="/"){newDir="/"+newDir;}
			if(newDir.split("")[newDir.split("").length-1]!="/"){newDir=newDir+"/";}
			dirOperating=newDir;
			loadFileList(dirOperating);
		}
	}
	function startHoverSelect(ele){
		if(event.target.getAttribute("data-filename")){fileName=event.target.getAttribute("data-filename")}else{fileName=event.target.parentNode.getAttribute("data-filename")}
		if(fileSelected.indexOf(fileName)==-1){fileHoverSelecting="select";}else{fileHoverSelecting="unselect";}
	}
	function hoverSelect(ele){
		fileName=ele.getAttribute("data-filename");
		if(fileHoverSelecting){
			if(fileHoverSelecting=="select"){
				if(fileSelected.indexOf(fileName)==-1){
					fileSelected.push(fileName);			
					loadFileSelected();
				}
			}else{
				fileSelected=fileSelected.filter(item=>item!==fileName);
				loadFileSelected();
			}
		}
	}
	function viewFile(ele,byname,restoreDirOperating){
		if(!byname){
			fileIsDir=ele.getAttribute("data-isdir");
			fileName=ele.getAttribute("data-filename");
		}else{fileIsDir=false;fileName=ele;}
		if(fileSelected.length==0){
			offsetBeforeEditing=pageYOffset;
			fileType=fileName.split(".")[fileName.split(".").length-1].toLowerCase();
			fileEditing=fileName;
			if(fileIsDir=="true"){
				dirOperating=dirOperating+fileName+"/";
				loadFileList(dirOperating);
			}else{
				textMode=null;
				if(fileType=="html"||fileType=="htm"||fileType=="txt"){textMode="html";}
				else if(fileType=="php"){textMode="php";}
				else if(fileType=="json"){textMode="json";}
				else if(fileType=="js"){textMode="javascript";}
				else if(fileType=="css"){textMode="css";}
				else if(fileType=="xml"||fileType=="yml"||fileType=="xaml"){textMode="xml";}
				else if(fileType=="zip"){if(confirm("您是否想解压此文件 ~(￣▽￣)~*\nTip: 部分环境可能不支持此功能")){
					let destDir=prompt("要解压到哪个目录捏 (*^▽^*)",dirOperating);
					if(destDir){
						if(destDir.split("")[0]!="/"){destDir="/"+destDir;}
						if(destDir.split("")[destDir.split("").length-1]!="/"){destDir=destDir+"/";}
						showModule("loading");request("unzip","name="+dirOperating+fileName+"&dir="+destDir,function(code){
							if(code==1001){alert("您使用的环境貌似不支持此功能（＞人＜；）")}
							else if(code==1002){alert("您指定的目录不存在 (´。＿。｀)")}
							else if(code==1003){alert("找不到此压缩包，请尝试刷新此页面（＞人＜；）");}
							else{alert("可能出现未知错误，请尝试刷新此页面（＞人＜；）");}
							loadFileList(dirOperating,true);
						})
					}
				}}
				else if(fileType=="rar"||fileType=="7z"){alert("不支持此类文件解压，请使用.zip格式 (っ´Ι`)っ");}
				else if(fileType=="jpg"||fileType=="png"||fileType=="jpeg"||fileType=="gif"||fileType=="webp"||fileType=="ico"){
					showModule("imgviewer");
					showMenu("imgviewer");
					imageViewingUrl="?a=down&pwd="+encodeURIComponent(localStorage.getItem("FileAdmin_Password"))+"&name="+encodeURI(dirOperating+fileName);
					document.getElementById("imgviewer").src=imageViewingUrl;
				}else if(fileType=="mp4"||fileType=="webm"||fileType=="mp3"){
					showModule("vidviewer");
					showMenu("vidviewer");
					vidViewingUrl="?a=down&pwd="+encodeURIComponent(localStorage.getItem("FileAdmin_Password"))+"&name="+encodeURI(dirOperating+fileName);
					document.getElementById("vidviewer").src=vidViewingUrl;
				}else if(fileType=="fajs"){alert("您不能直接打开.fajs文件，请打开同名的.js文件哦~")}
				else{if(confirm("此文件的格式目前不被支持捏..\n您是否希望尝试使用文本编辑器打开 (⊙_⊙)？")){textMode="html"}}
				if(textMode){
					showModule("loading");
					request("getfile","name="+dirOperating+fileName,function(c,d,file){
						if(fileType=="js"){
							document.getElementById("obfuscateBtn").style.display="inline-block";
							if(localStorage.getItem("FileAdmin_Settings_Obfuscator")=="1"){document.getElementById("obfuscateBtn").innerText="关闭混淆"}
							else{document.getElementById("obfuscateBtn").innerText="启用混淆"}
						}else{document.getElementById("obfuscateBtn").style.display="none"}
						if(navigator.maxTouchPoints>0){document.getElementById("mobileFastInput").style.top="0"}
						ace.config.set('basePath','https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/')
						textEditor=ace.edit("textEditor");
						textEditor.setOption("enableLiveAutocompletion",true);
						textEditor.session.setValue(file);
						textEditor.setTheme("ace/theme/chrome");
						textEditor.gotoLine(1);
						textEditor.setShowPrintMargin(false);
						textEditor.session.setMode("ace/mode/"+textMode);
						showModule("texteditor");
						showMenu("texteditor");
						document.title=fileName+" | FileAdmin";
						lastSaveContent=textEditor.getValue();
					});
				}
			}
		}else{
			if(fileSelected.indexOf(fileName)==-1){
				fileSelected.push(fileName);
				loadFileSelected();
			}else{
				fileSelected=fileSelected.filter(item=>item!==fileName);
				loadFileSelected();
			}
		}
		if(restoreDirOperating){dirOperating="/";}
	}
	function previousDir(){history.back(-1)}
	function arrToDir(item){
		dirName+=item+"/"
	}
	function loadFileMenu(){
		if(document.querySelector(".files.shown")){
			if(fileSelected.length==0){showMenu("files-noselect")}
			else if(fileSelected.length==1){showMenu("files-singleselect")}
			else{showMenu("files-multiselect")}
			if(moveOrCopyMode){document.getElementById("pasteBtn").style.display="inline-block"}else{document.getElementById("pasteBtn").style.display="none"}
		}
	}
	function loadFileSelected(){Array.prototype.slice.call(document.getElementsByClassName("file")).forEach(checkFileSelected);loadFileMenu();}
	function checkFileSelected(ele){
		if(fileSelected.indexOf(ele.getAttribute("data-filename"))==-1){ele.classList.remove("selected")}else{ele.classList.add("selected")}
	}
//========================================无选中操作
	function zipCurrentDir(){
		if(confirm("您确实想将当前目录打包为Zip文件嘛 (⊙_⊙)？\nTip: 部分环境可能不支持此功能")){
			showModule("loading")
			request("zip","name="+encodeURIComponent(dirOperating),function(code){
				if(code==1001){alert("文件打包失败..（＞人＜；）")}
				loadFileList(dirOperating,true);
			})
		}
	}
	function newFile(){
		let filename=prompt("📄 请输入新文件名称 (●'◡'●)");
		if(filename){
			showModule("loading")
			if(filename.indexOf("/")==-1){
				request("save","name="+encodeURIComponent(dirOperating+filename),function(){loadFileList(dirOperating,true)});
			}else{alert("文件名不能包含特殊字符呐 (；′⌒`)");}
		}
	}
	function newDir(){
		let filename=prompt("📂 请输入新目录名称 (●'◡'●)");
		if(filename){
			showModule("loading")
			if(filename.indexOf("/")==-1){
				request("mkdir","name="+encodeURIComponent(dirOperating+filename),function(){loadFileList(dirOperating,true)});
			}else{alert("目录名不能包含特殊字符呐 (；′⌒`)");}
		}
	}
	function openFileFinder(){
		document.getElementById("searchAddrBar").innerText="当前查找目录："+document.getElementById("addressBar").innerText;
		showModule("search");
		showMenu("search");
		document.getElementById("searchResult").innerHTML='<div style="padding:50px 0;opacity:.5;text-align:center">您还没有发起搜索 ㄟ( ▔, ▔ )ㄏ</div>';
		document.getElementById("replaceBtn").style.display="none";
	}
//========================================单选中操作
	function renameFile(){
		let newName=prompt("请输入文件的新名称(*^▽^*)",fileSelected[0]);
		if(newName){
			if(newName.indexOf("/")==-1&&newName.indexOf("&")==-1){
				showModule("loading");
				request("rename","dir="+encodeURIComponent(dirOperating)+"&old="+encodeURIComponent(fileSelected[0])+"&new="+encodeURIComponent(newName),function(c){
					if(c==1002){alert("文件 “"+newName+"” 已经存在啦 (；′⌒`)")}else if(c!=200){alert("出现未知错误 (；′⌒`)")}
					loadFileList(dirOperating,true)
				});
			}else{alert("文件名不可包含特殊字符哦 (；′⌒`)")}
		}
	}
	function downCurrFile(){
		if(document.querySelector(".file.selected").getAttribute("data-isdir")=="true"){alert("不支持直接下载文件夹捏..")}else{
			downUrl="?a=down&pwd="+encodeURIComponent(localStorage.getItem("FileAdmin_Password"))+"&name="+encodeURI(dirOperating+fileSelected[0]);
			location=downUrl;
		}
	}
//========================================单多选通用操作
	function delFile(){
		let fileDelStr=JSON.stringify(fileSelected);
		if(confirm("您确实要永久删除选中的文件和目录嘛 (⊙_⊙)？")){
			showModule("loading");
			request("del","files="+encodeURIComponent(fileDelStr)+"&dir="+dirOperating,function(){loadFileList(dirOperating,true)});
		}
	}
	function setMoveFiles(){
		moveOrCopyMode="move";
		moveOrCopyFromDir=dirOperating;
		moveOrCopyFiles=JSON.stringify(fileSelected);
		fileSelected=[];loadFileSelected();
	}
	function setCopyFiles(){
		moveOrCopyMode="copy";
		moveOrCopyFromDir=dirOperating;
		moveOrCopyFiles=JSON.stringify(fileSelected);
		fileSelected=[];loadFileSelected();
	}
	function filePaste(){
		if(moveOrCopyMode){
			showModule("loading");
			request(moveOrCopyMode,"files="+moveOrCopyFiles+"&from="+moveOrCopyFromDir+"&to="+dirOperating,function(){loadFileList(dirOperating,true);})
			moveOrCopyMode=null;document.getElementById("pasteBtn").style.display="none";
		}
	}
//========================================文本编辑器
	function saveFile(forceDisableObfuscator){
		document.getElementById("saveBtn").innerText="······";
		document.getElementById("loadingAnimations").classList.add("shown");
		if(!forceDisableObfuscator && fileEditing.split(".")[fileEditing.split(".").length-1].toLowerCase()=="js" && localStorage.getItem("FileAdmin_Settings_Obfuscator")=="1"){
			try{
				let obfuscated=JavaScriptObfuscator.obfuscate(textEditor.getValue(),{compact:true,controlFlowFlattening:true,controlFlowFlatteningThreshold:1,numbersToExpressions:true,simplify:true,stringArrayShuffle:true,splitStrings:true,stringArrayThreshold:1})._obfuscatedCode
				request("fajssave","name="+dirOperating+fileEditing+"&original="+encodeURIComponent(textEditor.getValue())+"&obfuscate="+encodeURIComponent(obfuscated),function(code){
					document.getElementById("loadingAnimations").classList.remove("shown");
					if(code==200){
						lastSaveContent=textEditor.getValue()
						document.getElementById("saveBtn").innerText="完成";
						setTimeout(function(){document.getElementById("saveBtn").innerHTML="保存<contextmenukey>Ctrl + S</contextmenukey>";},700)
					}else{
						alert("出现未知错误（＞人＜；）");
						document.getElementById("saveBtn").innerHTML="保存<contextmenukey>Ctrl + S</contextmenukey>";
					}
				})
			}catch(err){
				alert("混淆器出现错误，正在为您保存原代码 `(*>﹏<*)′\n\n"+err+"\n\n请检查代码中是否存在错误~");saveFile(true);
			}
		}else{
			request("save","name="+dirOperating+fileEditing+"&data="+encodeURIComponent(textEditor.getValue()),function(code){
				document.getElementById("loadingAnimations").classList.remove("shown");
				if(code==200){
					lastSaveContent=textEditor.getValue()
					document.getElementById("saveBtn").innerText="完成";
					setTimeout(function(){document.getElementById("saveBtn").innerHTML="保存<contextmenukey>Ctrl + S</contextmenukey>";},700)
				}else{
					alert("出现未知错误（＞人＜；）");
					document.getElementById("saveBtn").innerHTML="保存<contextmenukey>Ctrl + S</contextmenukey>";
				}
			})
		}
	}
	function setWrap(ele){
		if(textEditor.getSession().getUseWrapMode()==true){
			textEditor.getSession().setUseWrapMode(false);
			ele.innerText="关闭";
			setTimeout(function(){ele.innerText="换行"},700)
		}else{
			textEditor.getSession().setUseWrapMode(true)
			ele.innerText="启用";
			setTimeout(function(){ele.innerText="换行"},700)
		}
	}
	function setObfuscate(ele){
		if(localStorage.getItem("FileAdmin_Settings_Obfuscator")=="1"){
			localStorage.setItem("FileAdmin_Settings_Obfuscator","0")
			ele.innerText="启用混淆"
		}else{
			if(confirm("开启Js混淆前，请仔细阅读以下说明：\n\n- Js混淆可有效防止他人窃取您的Js源码\n- Js混淆会使您的Js文件存储占用成倍上涨\n- Js混淆可能会导致部分代码无法运行\n- 您可能难以调试混淆后的Js代码\n- Js混淆开启后，会在当前目录生成一个.fajs文件用于存储Js源文件\n- 请务必使用防火墙屏蔽他人对.fajs文件的访问\n- 请勿直接修改、移动或删除.fajs文件\n\n更多说明详见Github项目主页，是否仍要开启Js混淆功能？")){
				localStorage.setItem("FileAdmin_Settings_Obfuscator","1")
				ele.innerText="关闭混淆"
			}
		}
	}
	function reloadEditor(){
		if(textEditor.getValue()!=lastSaveContent){
			if(confirm("您有内容还没有保存哦，确实要刷新嘛？")){viewFile(fileEditing,true)}
		}else{viewFile(fileEditing,true)}
	}
//========================================右键菜单
	function showContextMenu(){
		if(navigator.maxTouchPoints==0){
			hideContextMenu();
			if(document.querySelector(".menu.shown")){
				event.preventDefault();
				let menuElem=document.createElement("contextmenu");
				menuElem.innerHTML=document.querySelector(".menu.shown").innerHTML;
				menuElem.onmousedown=function(){event.stopPropagation();}
				menuElem.onclick=function(){event.stopPropagation();hideContextMenu();}
				menuElem.style.top=event.clientY+"px";
				menuElem.style.left=event.clientX+"px";
				if(event.clientX>document.getElementsByTagName("html")[0].clientWidth-150){menuElem.style.left=event.clientX-150+"px";}
				document.body.appendChild(menuElem);
			}
		}
	}
	function hideContextMenu(){
		if(document.querySelector("contextmenu")){document.querySelector("contextmenu").remove()}
	}
	function fileContextMenu(ele){
		if(fileSelected.length<2){
			event.stopPropagation();
			navigator.vibrate([100]);
			fileSelected=[ele.getAttribute("data-filename")];
			loadFileSelected();
			showContextMenu();
		}else{
			showContextMenu();
		}
	}
//========================================重量级文件搜索
	function startSearch(){
		showModule("loading")
		if(document.getElementById("searchMode").value=="1"){
			request("find_by_name","type="+encodeURIComponent(document.getElementById("searchType").value)+"&find="+encodeURIComponent(document.getElementById("searchContent").value)+"&case="+encodeURIComponent(document.getElementById("searchCase").value)+"&dir="+encodeURIComponent(searchDir),function(c,d){
				searchedArr=JSON.parse(decodeURIComponent(d));
				searchResultHtml="";
				searchedArr.forEach(addToSearchResultHtml);
				showModule("search");showMenu("search")
				document.getElementById("searchResult").innerHTML=searchResultHtml;
				if(searchResultHtml==""){document.getElementById("searchResult").innerHTML='<div style="padding:50px 0;opacity:.5;text-align:center">没有找到符合条件的文件 ㄟ( ▔, ▔ )ㄏ</div>';}
			})
		}else{
			request("find_by_content","type="+encodeURIComponent(document.getElementById("searchType").value)+"&find="+encodeURIComponent(document.getElementById("searchContent").value)+"&case="+encodeURIComponent(document.getElementById("searchCase").value)+"&dir="+encodeURIComponent(searchDir),function(c,d){
				searchedArr=JSON.parse(decodeURIComponent(d));
				searchResultHtml="";
				searchedArr.forEach(addToSearchResultHtml);
				showModule("search");showMenu("search")
				document.getElementById("searchResult").innerHTML=searchResultHtml;
				if(document.getElementById("searchMode").value=="3"){document.getElementById("replaceBtn").style.display="inline-block"}
				if(searchResultHtml==""){
					document.getElementById("searchResult").innerHTML='<div style="padding:50px 0;opacity:.5;text-align:center">没有找到符合条件的文件 ㄟ( ▔, ▔ )ㄏ</div>';
					document.getElementById("replaceBtn").style.display="none"
				}
			})
		}
	}
	function addToSearchResultHtml(data){
		fileType=data.split(".")[data.split(".").length-1].toLowerCase();
		searchResultHtml=searchResultHtml+`<div class="file" data-filename="`+data.replace("//","/")+`" onclick='viewFile("`+data.replace("//","/")+`",true,true)'>
			`+getFileIco(fileType,false)+`
			<div class="fileName">`+data.replace("//","/")+`</div>
		</div>`;
	}
	function loadSearchMode(ele){
		if(ele.value=="3"){
			document.getElementById("replaceOptnInput").style.display="block"
			document.getElementById("replaceHidden").style.display="none"
			document.getElementById("searchCase").value="1"
		}else{
			document.getElementById("replaceOptnInput").style.display="none"
			document.getElementById("replaceBtn").style.display="none"
			document.getElementById("replaceHidden").style.display="block"
		}
	}
	function startChange(){
		if(confirm("替换操作具有危险性且不支持撤销，强烈建议执行前仔细核对文件列表并对整个目录打包备份。是否确认要继续 (⊙_⊙)？")){
			showModule("loading")
			request("replace","type="+encodeURIComponent(document.getElementById("searchType").value)+"&find="+encodeURIComponent(document.getElementById("searchContent").value)+"&replace="+encodeURIComponent(document.getElementById("searchReplaceContent").value)+"&dir="+encodeURIComponent(searchDir),function(c,d){
				alert("在"+d+"个文件中完成了替换操作 (*^▽^*)");
				openFileFinder();
			})
		}
	}
//========================================移动端输入面板
	function mobileInput(ele){
		textEditor.insert(ele.innerText);
		textEditor.focus();
	}
	function mobileEditorPrevious(){
		currentLine=textEditor.selection.getCursor().row+1;
		currentChar=textEditor.selection.getCursor().column;
		textEditor.gotoLine(currentLine,currentChar-1);
		textEditor.focus();
	}
	function mobileEditorNext(){
		currentLine=textEditor.selection.getCursor().row+1;
		currentChar=textEditor.selection.getCursor().column;
		textEditor.gotoLine(currentLine,currentChar+1);
		textEditor.focus();
	}
//========================================磁盘空间占用
	function getDiskSpaceInfo(){
		showModule("loading");
		request("space","name="+encodeURIComponent(dirOperating),function(c,data,d){
			if(c==200){
				let returnData=d.split("||");
				let total=humanSize(returnData[1]/10);
				let free=humanSize(returnData[2]/10);
				let freepercent=Math.round(returnData[2]/returnData[1]*10000)/100;
				let used=humanSize(returnData[3]/10);
				let usedpercent=Math.round(returnData[3]/returnData[1]*10000)/100;
				let current=humanSize(returnData[4]/10);
				let currentpercent=Math.round(returnData[4]/returnData[1]*10000)/100;
				if(returnData[1]!=0){alert("空间信息获取成功啦 ( •̀ ω •́ )✧\n\n磁盘空间合计："+total+"\n可用磁盘空间："+free+"（占总空间的"+freepercent+"%）"+"\n已用磁盘空间："+used+"（占总空间的"+usedpercent+"%）"+"\n当前目录占用："+current+"（占总空间的"+currentpercent+"%）");}
				else{alert("磁盘总空间获取失败，您使用的环境可能不允许此操作 `(*>﹏<*)′\n当前查看的目录占用"+current+"磁盘空间哦 ( •̀ ω •́ )✧")}
				loadFileList(dirOperating,true);
			}else if(c==1001){alert("您当前查看的目录不存在，可能已经被删除惹 /_ \\")}
			else{alert("出现未知错误惹 /_ \\");}
		})
	}
//========================================检查更新
	function chkupd(){
		showModule("loading")
		request("chkupd",null,function(c,d,o){
			if(o=="1001"){alert("您的FileAdmin已是最新版啦~");loadFileList(dirOperating,true)}
			else if(o=="1002"){alert("获取更新失败，您的服务器网络环境可能无法访问Vercel (；′⌒`)");loadFileList(dirOperating,true)}
			else{
				showModule("updinfo");showMenu("updinfo")
				document.getElementById("updinfo").innerHTML=o;
			}
		})
	}
	function applupd(){
		showModule("loading");
		request("applyversion",null,function(c){
			if(c==200){location.reload();}
			else{alert("更新失败惹..");history.back();showMenu("updinfo")}
		})
	}
//========================================退出登录
	function logout(){
		if(confirm("您真的要退出登录嘛？＞﹏＜")){
			localStorage.setItem("FileAdmin_Password",0);
			showModule("login");
		}
	}
//</script><?php }else{ ?>
<!--
	SimSoft FileAdmin 前端部分
	由盐鸡开发的一款轻量级文件管理器
	© 2022 SimSoft
-->
<!DOCTYPE html>
<html onmousedown="hideContextMenu()" oncontextmenu="showContextMenu()" onclick="if(!fileHoverSelecting){fileSelected=[];loadFileSelected();}" onmouseup="setTimeout(function(){fileHoverSelecting=false;},50)">
	<head>
		<title>FileAdmin</title>
		<meta name="viewport" content="width=device-width">
		<link rel="icon" href="//asset.simsoft.top/branding/projects/fileadmin.png">
		<link rel="stylesheet" href="?a=css">
	</head>
	<body>
		<div class="title">
			<div class="appName" onclick="chkupd()">File<b>Admin</b><div id="versionNote">正在获取</div></div>
			<svg id="logoutBtn" onclick="logout()" width="20" height="20" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="3"8" height="48" fill="white" fill-opacity="0.01"/><path d="M23.9917 6L6 6L6 42H24" stroke="#000000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M33 33L42 24L33 15" stroke="#000000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 23.9917H42" stroke="#000000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</div>
		<div class="module loading shown" data-module="loading" id="loadingAnimations">
			<div class="loadingAnimation">
				<div class="loadingAnimationDot" id="dot1">·</div>
				<div class="loadingAnimationDot" id="dot2">·</div>
				<div class="loadingAnimationDot" id="dot3">·</div>
			</div>
		</div>

		<!--登录页-->
		<div class="module login" data-module="login">
			<div class="loginTitle">登录 FileAdmin</div>
			<input id="passwordManagerUsername">
			<input autofocus id="loginPassword" placeholder="请输入密码 (/▽＼)" type="password" onkeydown="loginCheckEnter(event)"><button onclick="login()" class="loginBtn">→</button>
		</div>
		
		<!--文件列表页-->
		<div class="module files" data-module="files">
			<div class="addressBar"><button title="根目录" onclick="dirOperating='/';loadFileList('/')">/</button><button title="回退" onclick="previousDir()"><</button><div id="addressBar" onclick="editAddressBar()">/</div></div>
			<br><div id="fileList" onclick="event.stopPropagation();" onmousedown="if(event.button==0){startHoverSelect(this)}"></div>
		</div>
		<div class="menu" data-menu="files-noselect" onclick="event.stopPropagation();">
			<button onclick="fileSelected=fileListOperating;loadFileSelected();">全选<contextmenukey>Ctrl + A</contextmenukey></button>
			<button onclick="loadFileList(dirOperating,true)">刷新<contextmenukey>F5</contextmenukey></button>
			<button onclick="showMenu('files-upload')">上传</button>
			<button onclick="zipCurrentDir()">打包</button>
			<button onclick="showMenu('files-newfile')">新建</button>
			<button onclick="openFileFinder();searchDir=dirOperating;dirOperating=''" class="big">查找文件</button>
			<button onclick="getDiskSpaceInfo()" class="big">占用情况</button>
			<button onclick="filePaste()" id="pasteBtn" style="display:none">粘贴<contextmenukey>Ctrl + V</contextmenukey></button>
		</div>
		<div class="menu" data-menu="files-singleselect" onclick="event.stopPropagation();">
			<button onclick="fileSelected=fileListOperating;loadFileSelected();">全选<contextmenukey>Ctrl + A</contextmenukey></button>
			<button onclick="fileSelected=[];loadFileSelected();" class="big">取消选中</button>
			<button onclick="renameFile();">改名</button>
			<button onclick="downCurrFile();">下载</button>
			<button onclick="setMoveFiles();">剪切<contextmenukey>Ctrl + X</contextmenukey></button>
			<button onclick="setCopyFiles();">复制<contextmenukey>Ctrl + C</contextmenukey></button>
			<button onclick="delFile();">删除<contextmenukey>Delete</contextmenukey></button>
		</div>
		<div class="menu" data-menu="files-multiselect" onclick="event.stopPropagation();">
			<button onclick="fileSelected=fileListOperating;loadFileSelected();">全选<contextmenukey>Ctrl + A</contextmenukey></button>
			<button onclick="fileSelected=[];loadFileSelected();" class="big">取消选中</button>
			<button onclick="setMoveFiles();">剪切<contextmenukey>Ctrl + X</contextmenukey></button>
			<button onclick="setCopyFiles();">复制<contextmenukey>Ctrl + C</contextmenukey></button>
			<button onclick="delFile();">删除<contextmenukey>Delete</contextmenukey></button>
		</div>
		<div class="menu" data-menu="files-upload">
			<button class="big" onclick="document.getElementById('filesUploadInput').click()">上传文件</button>
			<button class="big" onclick="document.getElementById('folderUploadInput').click()">上传目录</button>
			<button onclick="loadFileMenu();">取消</button>
		</div>
		<div class="menu" data-menu="files-newfile">
			<button onclick="newDir()" class="big">新建目录</button>
			<button onclick="newFile()" class="big">新建文件</button>
			<button onclick="loadFileMenu();">取消</button>
		</div>
		
		<!--文件上传器-->
		<div class="module upload" data-module="upload">
			<div style="font-size:1.5em;text-align:center;">正在上传 ψ(._. )></div>
			<div class="uploadProgress"><div id="uploadProgressBar"></div></div>
			<div class="uploadText">当前上传：<span id="uploadText-CurrFile"></span></div>
			<div class="uploadText">当前进度：<span id="uploadText-CurrProg"></span></div>
			<div class="uploadText">当前速度：<span id="uploadText-CurrSpeed"></span></div>
			<div class="uploadText">目标目录：根目录<span id="uploadText-DestDir"></span></div>
			<div class="uploadText">等待上传：<span id="uploadText-Waiting"></span> 个文件</div>
		</div>
		
		<!--纯文本编辑器-->
		<div class="module texteditor" data-module="texteditor">
			<div id="textEditor"></div>
		</div>
		<div id="mobileFastInput">
			<div class="mobileInputBtn" onclick="mobileInput(this)"><</div>
			<div class="mobileInputBtn" onclick="mobileInput(this)">></div>
			<div class="mobileInputBtn" onclick="mobileInput(this)">{</div>
			<div class="mobileInputBtn" onclick="mobileInput(this)">}</div>
			<div class="mobileInputBtn" onclick="mobileInput(this)">(</div>
			<div class="mobileInputBtn" onclick="mobileInput(this)">)</div>
			<div class="mobileInputBtn" onclick="mobileInput(this)">%</div>
			<div class="mobileInputBtn" onclick="mobileInput(this)">/</div>
			<div class="mobileInputBtn" onclick="mobileInput(this)">\</div>
			<div class="mobileInputBtn" onclick="mobileInput(this)">=</div>
			<div class="mobileInputBtn" onclick="mobileInput(this)">"</div>
			<div class="mobileInputBtn" onclick="mobileInput(this)">'</div>
			<div class="mobileInputBtn" onclick="mobileEditorPrevious()">←</div>
			<div class="mobileInputBtn" onclick="mobileEditorNext()">→</div>
		</div>
		<div class="menu" data-menu="texteditor">
			<button onclick="setObfuscate(this)" id="obfuscateBtn" class="big"></button>
			<button onclick="saveFile()" id="saveBtn">保存<contextmenukey>Ctrl + S</contextmenukey></button>
			<button onclick="reloadEditor()">刷新<contextmenukey>F5</contextmenukey></button>
			<button onclick="setWrap(this)">换行</button>
			<button onclick="window.open('.'+dirOperating+fileEditing)">预览</button>
			<button onclick="history.back()">返回<contextmenukey>ESC</contextmenukey></button>
		</div>
		<!--图片预览器-->
		<div class="module imgviewer" data-module="imgviewer"><img id="imgviewer"></div>
		<div class="menu" data-menu="imgviewer">
			<button onclick="location=imageViewingUrl" class="big">下载图片</button>
			<button onclick="document.getElementById('imgviewer').src='';history.back();">返回</button>
		</div>
		<!--视频播放器-->
		<div class="module vidviewer" data-module="vidviewer"><video controls id="vidviewer" autoplay></video></div>
		<div class="menu" data-menu="vidviewer">
			<button onclick="location=vidViewingUrl" class="big">下载视频</button>
			<button onclick="document.getElementById('vidviewer').src='';history.back();">返回</button>
		</div>
		
		<!--重量级文件搜索器-->
		<div class="module search" data-module="search">
			<div class="addressBar" id="searchAddrBar"></div><br>
			<div id="searchOptnArea" style="padding:10px">
				<div><span>查找内容</span><input id="searchContent" autocomplete="off" placeholder="输入要搜索的文件名/文件内容 q(≧▽≦q)"></div>
				<div><span>查找格式</span><input value="html php css js" id="searchType" autocomplete="off" placeholder="空格分隔，留空则查找所有文件 ( •̀ ω •́ )✧"></div>
				<div id="replaceOptnInput" style="display:none"><span>替换内容</span><input id="searchReplaceContent" placeholder="输入要替换为的文件内容 §(*￣▽￣*)§"></div>
				<div><span>工作模式</span><select id="searchMode" onchange="loadSearchMode(this)"><option value="1">仅匹配文件名</option><option value="2">匹配文件内容</option><option value="3">查找并替换文件内容</option></select></div>
				<div id="replaceHidden"><span>区分大小写</span><select id="searchCase"><option value="1">开启</option><option value="2">关闭</option></select></div>
			</div><br>
			<div id="searchResult"></div>
		</div>
		<div class="menu" data-menu="search">
			<button onclick="startSearch()" class="big">开始查找</button>
			<button onclick="startChange()" style="display:none" class="big" id="replaceBtn">确认替换</button>
			<button onclick="dirOperating='/';history.back();">退出</button>
		</div>
			
		<!--更新信息-->
		<div class="module updinfo" data-module="updinfo">
			<div style="font-size:1.5em;border-bottom:1px solid #f5f5f5;text-align:center;padding:10px;">检测到更新</div>
			<div id="updinfo"></div>
		</div>
		<div class="menu" data-menu="updinfo">
			<button onclick="applupd()" class="big">应用更新</button>
			<button onclick="history.back()">取消</button>
		</div>
		
		<div style="display:none">
			<input type="file" multiple webkitdirectory id="folderUploadInput" onchange="addDirToUploads(this)">
			<input type="file" multiple id="filesUploadInput" onchange="addFilesToUploads(this)">
		</div>
	</body>
	<script src="?a=js"></script>
	<script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/ace.min.js"></script>
	<script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/mode-javascript.min.js"></script>
	<script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/mode-html.min.js"></script>
	<script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/mode-php.min.js"></script>
	<script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/mode-css.js"></script>
	<script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/mode-json.min.js"></script>
	<script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/theme-chrome.js"></script>
	<script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/ext-language_tools.min.js"></script>
	<script src="https://asset.simsoft.top/products/fileadmin/obfuscator.js"></script>
</html><?php } ?>
