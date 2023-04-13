<?php $PASSWORD="kotaserang12"; $VERSION=7.13;


	/* 设置不进行报错以免影响运行 */
	error_reporting(0);
	
	/* 扫描目录下全部文件函数 */
	function scandirAll($dir,$first=false){	
		$files = [];
		$child_dirs = scandir($dir);
		foreach($child_dirs as $child_dir){if($child_dir != '.' && $child_dir != '..'){
			if(is_dir($dir."/".$child_dir)){$files=array_merge($files,scandirAll($dir."/".$child_dir));}
			else{array_push($files,$dir."/".$child_dir);}
		}}
		return $files;
	}
	
	/* 打包目录函数 */
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
	
	/* 解包.zip文件函数 */
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
	
	/* 删除目录函数 */
	function unlinkDir($dir){
		$files=scandir($dir);
		foreach ($files as $key => $filename) {
			if($filename!="."&&$filename!=".."){
				if(is_dir($dir."/".$filename)){unlinkDir($dir."/".$filename);}else{unlink($dir."/".$filename);}
			}
		}
		rmdir($dir);
	}
	
	/* 支持同时创建多层目录函数 */
	function nbMkdir($pathname){
		$paths = explode("/", $pathname);
		$nowp = "";
		foreach($paths as $key=>$value) {
			$nowp .= $value . "/";
			if ($value == "." || $value == ".." || $value == "") continue;
			mkdir($nowp);
		}
	}
	
	/* 复制文件(夹)函数 */
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
	
	/* 计算目录体积函数 */
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
	
	/* 主体 PHP 操作 */
	$ACT=$_POST["a"];$PWD=$_POST["pwd"];
	if($ACT){
		/* 进行登录 */
		if($ACT=="login"){
			if($_POST["loginPwd"]==$PASSWORD){echo "200||".password_hash($PASSWORD.date("Ymd"),PASSWORD_DEFAULT);}else{echo "1001";}
			
		/* 如果密码验证成功 */
		}elseif(password_verify($PASSWORD.date("Ymd"),$PWD)){
			
			/* 页面加载时验证状态，密码正确时始终kembali成功 */
			if($ACT=="check"){
				echo "200";
				
			/* kembali指定目录的文件列表 */
			}elseif($ACT=="files"){
				/* 阻止用户访问上级目录 */
				if(strstr($_POST["name"],"./")){
					echo "1002";
				/* 如果请求过来的文件夹存在，则输出内容 */
				}elseif(is_dir(".".$_POST["name"])){
					$fileArray=scandir(".".$_POST["name"]);
					$fileArrayModified=[];
					/* 先输出目录列表 */
					foreach($fileArray as $filename){
						$fileisdir=is_dir(".".$_POST["name"].$filename);
						if($fileisdir){
							$filesize=0;
							array_push($fileArrayModified,array("name"=>$filename,"dir"=>$fileisdir,"size"=>$filesize));
						}
					}
					/* 再输出文件列表 */
					foreach($fileArray as $filename){
						$fileisdir=is_dir(".".$_POST["name"].$filename);
						if(!$fileisdir){
							$filesize=filesize(".".$_POST["name"].$filename)/1024;
							array_push($fileArrayModified,array("name"=>$filename,"dir"=>$fileisdir,"size"=>$filesize));
						}
					}
					/* 此处遍历两次是要按顺序输出，先目录再文件夹，便于管理+美观 */
					echo "200||".rawurlencode(json_encode($fileArrayModified));
				}else{
					echo "1001";
				}
			
			/* 输出textEditor需要的文本内容 */	
			}elseif($ACT=="getfile"){
				/* 此处为js加密适配，如果请求的文件有同名的.fajs文件存在，则输出.fajs中没加密的内容便于用户进行编辑，如果没有就直接输出原文件内容 */
				if(file_exists(".".$_POST["name"].".fajs")){echo file_get_contents(".".$_POST["name"].".fajs");}else{echo file_get_contents(".".$_POST["name"]);}
				
			/* 使用textEditorsave普通文件 */
			}elseif($ACT=="save"){
				file_put_contents(".".$_POST["name"],$_POST["data"]);
				/* 这里如果有同名fajs文件则进行删除，因为这个方法是没有加密时进行的，如果fajs不删，下次输出的还是老的fajs文件就对不上了 */
				if(file_exists(".".$_POST["name"].".fajs")){unlink(".".$_POST["name"].".fajs");}
				if(file_exists(".".$_POST["name"]) && file_get_contents(".".$_POST["name"]) == $_POST["data"]){echo "200";}else{echo "1002";}
				
			/* 使用textEditorsave加密的js文件，这里会存俩文件，fa本身没有解密js的能力所以原文件一定要存一份 */
			}elseif($ACT=="fajssave"){
				/* 这里原文件存进同名fajs，加密文件存进js，这样方便管理而且用不着用户自己去改资源地址 */
				file_put_contents(".".$_POST["name"],$_POST["obfuscate"]);
				file_put_contents(".".$_POST["name"].".fajs",$_POST["original"]);
				if(file_exists((".".$_POST["name"])) && file_get_contents(".".$_POST["name"]) == $_POST["obfuscate"] && file_get_contents(".".$_POST["name"].".fajs") == $_POST["original"]){echo "200";}else{echo "1002";}

			/* 对当前目录进行打包 */
			}elseif($ACT=="zip"){
				$zipResult=create_zip(scandirAll(realpath(".".$_POST["name"]),true),".".$_POST["name"]."FileAdmin_".time().".zip",false);
				if($zipResult){echo "200";}else{echo "1001";}
				
			/* 解包压缩包 */
			}elseif($ACT=="unzip"){
				echo unzip_file(".".$_POST["name"],".".$_POST["dir"],false);
				
			/* 新建目录 */
			}elseif($ACT=="mkdir"){
				mkdir(".".$_POST["name"]);
				if(file_exists(".".$_POST["name"])){echo "200";}else{echo "1002";}
				
			/* 给文件(夹)改名 */
			}elseif($ACT=="rename"){
				/* 这里判断一下是不是存在同名的文件，否则就直接覆盖掉了，寄 */
				if(!file_exists(".".$_POST["dir"].$_POST["new"])){
					rename(".".$_POST["dir"].$_POST["old"],".".$_POST["dir"].$_POST["new"]);
					if(file_exists(".".$_POST["dir"].$_POST["new"])){
					    echo "200";
					}else{
					    echo "1003";
					}
				}else{
					echo "1002";
				}
				
			/* 删除文件(夹) */
			}elseif($ACT=="del"){
				$delFiles=json_decode(rawurldecode($_POST["files"]));
				foreach($delFiles as $filename){
					$trueFileName=".".$_POST["dir"].$filename;
					/* 这里进行判断，如果是文件就直接干掉，是目录就用上面的unlinkDir干掉 */
					if(is_dir($trueFileName)){unlinkDir($trueFileName);}else{unlink($trueFileName);}
					if(file_exists($trueFileName)){echo "1";}
				}
				echo "200";
				
			/* 检查本体更新 */
			}elseif($ACT=="chkupd"){
				/* 从我站api服务获取最新版本，如果和当前版本不同就弹更新 */
				$latest=file_get_contents("https://api.simsoft.top/fileadmin/latest/?stamp=".time());
				if($latest && $latest!=$VERSION){
					/* 获取这次版本的内容 */
					$updinfo=file_get_contents("https://api.simsoft.top/fileadmin/updateinfo/?stamp=".time());
					if($updinfo){
						echo $updinfo;
					}else{echo "1002";}
				}else{echo "1001";}
				
			/* 应用版本更新 */
			}elseif($ACT=="applyversion"){
				/* 先从我站下载一个FileAdminUpdater.php用于替换主文件，因为自己更新本体试了会出问题 */
				$updater=file_get_contents("https://api.simsoft.top/fileadmin/updater/?stamp=".time());
				if($updater){
					file_put_contents("./FileAdminUpdater.php",$updater);
					header("location: ./FileAdminUpdater.php?famain=".end(explode("/",$_SERVER['PHP_SELF'])));
					/* 进行一下重定向，更新完updater会自删，基本没有安全隐患 */
				}else{echo "1001";}
				
			/* 进行文件复制 */
			}elseif($ACT=="copy"){
				$operateFiles=json_decode(rawurldecode($_POST["files"]));
				foreach($operateFiles as $filename){
					$fromfile=".".$_POST["from"].$filename;
					$tofile=".".$_POST["to"].$filename;
					if(is_dir($fromfile)){copyDir($fromfile.'/',".".$_POST["to"].$filename."/");}else{copy($fromfile,$tofile);}
				}
				
			/* 进行文件移动，这个比较简单直接遍历rename就完了 */
			}elseif($ACT=="move"){
				$operateFiles=json_decode(rawurldecode($_POST["files"]));
				foreach($operateFiles as $filename){
					$fromfile=".".$_POST["from"].$filename;
					$tofile=".".$_POST["to"].$filename;
					rename($fromfile,$tofile);
				}
				
			/* 通过文件内容搜索文件 */
			}elseif($ACT=="find_by_content"){
				$trueDirName=".".implode("/",explode("/",$_POST["dir"]));
				$filelist=scandirAll($trueDirName);
				$searchedFiles=[];
				/* 这个地方设置用户填的文件类型，空格分隔的；用textFile这个名字是因为初期只能搜索文本后来懒得改变量名了 */
				$textFiles=explode(" ",$_POST["type"]);
				/* 文件列表进行遍历 */
				foreach($filelist as $filenameFound){
					/* 如果post过来type是空的(即用户想搜索所有类型的文件)，或者文件类型在允许列表里边，就进行处理，否则不输出 */
					if($_POST["type"]=='' || in_array(strtolower(end(explode(".",$filenameFound))),$textFiles)){
						$filedata=file_get_contents($filenameFound);
						/* 判断文件内容里是否含搜的东西，case用于指定大小写 */
						if($_POST["case"]=="1"){$fileInNeed=strstr($filedata,$_POST["find"]);}else{$fileInNeed=stristr($filedata,$_POST["find"]);}
						/* 如果文件符合就push到输出的内容里 */
						if($fileInNeed){array_push($searchedFiles,str_replace("./","/",$filenameFound));}
					}
				}
				echo "200||".rawurlencode(json_encode($searchedFiles));
				
			/* 通过文件名搜索文件，工作原理和上面的完全一致就不写注释了 */
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
				
			/* 进行文件替换，工作原理也差不多 */
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
				
			/* 获取当前目录的占用信息 */
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
	
	/* 下载文件 */
	}elseif(password_verify($PASSWORD.date("Ymd"),$_GET["pwd"]) && $_GET["a"]=="down"){
		/* 指定大小以便浏览器显示进度条，但是大文件还是会玄学失效，原因未知 */
		header("content-length: ".filesize(".".$_GET["name"]));
		/* 要求浏览器下载文件 */
		header("content-disposition: attachment;filename=".rawurlencode(end(explode("/",$_GET["name"]))));
		echo file_get_contents(".".$_GET["name"]);
		
	/* upload文件 */
	}elseif(password_verify($PASSWORD.date("Ymd"),$_GET["pwd"]) && $_GET["a"]=="upload"){
		$destDir=".".$_GET["dir"];
		if(!is_dir($destDir)){nbMkdir($destDir);}
		if(file_exists($destDir.$_FILES["file"]["name"])){echo "1002";}else{
		    move_uploaded_file($_FILES["file"]["tmp_name"],$destDir.$_FILES["file"]["name"]);
		    if(file_exists($destDir.$_FILES["file"]["name"])){echo "200";}else{echo "1001";}
		}
		
	/* 在加载时获取版本信息 */
	}elseif($_GET["a"]=="ver"){
		$latest=file_get_contents("https://api.simsoft.top/fileadmin/latest/?stamp=".time());
		if($latest && $latest!=$VERSION){echo "1001";}else{echo "v".$VERSION;}
		
	}elseif($_GET["a"]=="css"){ 
		header("content-type: text/css");
?>/* <style> */
#passwordManagerUsername{display:none}
*{box-sizing:border-box;}
body{margin:0;user-select:none;margin-top:45px;font-family:微软雅黑;background:#f5f5f5;min-height:100%;}
::-webkit-scrollbar{display:none;}
.title{position:fixed;top:0;left:0;right:0;height:fit-content;box-shadow:0 0 5px 0 rgba(0,0,0,.4);height:40px;background:white;z-index:5;vertical-align:top;}
.appName{font-size:1.5em;position:absolute;top:0;height:fit-content;bottom:0;left:10px;margin:auto}
.appName b{color:#1e9fff;}
#versionNote{border-radius:10px 10px 10px 0;background:#f5f5f5;display:inline-block;margin-left:5px;color:#ababab;padding:0 5px;font-size:.4em;vertical-align:top}
#versionNote.active{background:#1e9fff;color:white}
.title #logoutBtn{position:absolute;top:0;bottom:0;right:35px;margin:auto;transform:rotate(180deg)}
.title #skinBtn{position:absolute;top:0;bottom:0;right:10px;margin:auto;}
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
#textEditor{position:absolute;top:40px;left:0;right:0;height:calc(100% - 40px);}
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
#vidviewer,#imgviewer{width:calc(100% - 10px);height:calc(100vh - 100px);background:white;margin:5px;border:1px solid rgba(0,0,0,.1);border-radius:5px;object-fit:contain;outline:none;}
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
#filesUploadInputContainer{display:none;position:fixed;inset:0;background:rgba(100,100,100,.5);z-index:30;}
#filesUploadInputContainer div{width:250px;height:fit-content;inset:0;margin:auto;position:fixed;padding:20px;border-radius:10px;text-align:center;border:2px dotted white;color:white}
#filesUploadInputContainer span{display:block;font-size:2em;}
#filesUploadInputContainer input{position:fixed;inset:0;width:100%;height:100%;opacity:0;}
@media screen and (max-width:700px) {
	.mobileInputAdded #mobileFastInput{bottom:0;}
	.mobileInputAdded .menu.shown{bottom:40px}
	.mobileInputAdded .title{display:none}
	.mobileInputAdded #textEditor{top:0px}
}
#mobileFastInput{position:fixed;bottom:-90px;height:40px;background:white;text-align:center;z-index:15;transition:top .2s;width:100vw;margin:auto;padding:5px 0;}
.mobileInputBtn.mode{background:#fafafa}
.mobileInputBtn{display:inline-block;border-radius:5px;padding:5px 2px;}
#fastInputHtm .mobileInputBtn{width:calc(100% / 8 - 5px);}
#fastInputJs .mobileInputBtn{width:calc(100% / 11 - 5px);}
#fastInputCss .mobileInputBtn{width:calc(100% / 9 - 5px);}
.mobileInputBtn:active{background:#eeeeee;}
contextmenu #saveMenuText{display:none}
.menu #saveContextMenuText{display:none}
@keyframes loadingDot{
	0%{transform:translateY(0px)}
	15%{transform:translateY(10px)}
	30%{transform:translateY(-10px)}
	45%{transform:translateY(5px)}
	60%{transform:translateY(5px)}
	75%{transform:translateY(0)}
}
@media screen and (min-width:701px) {
	.menu{top:-30px;transition:top .2s,opacity .2s;opacity:0;position:fixed;z-index:20;right:65px;left:150px;height:24px;text-align:right;}
	.menu button{outline:none;border:0;background:#f5f5f5;height:100%;width:45px;border-radius:5px;margin-left:5px;}
	.menu button.big{width:70px}
	.menu button:hover{background:#f9f9f9}
	.menu button:active{background:#f0f0f0}
	.menu.shown{top:8px;opacity:1;}
}
@media screen and (max-width:700px) {
	body{margin-bottom:50px;}
	.menu{bottom:-35px;transition:bottom .2s;box-shadow:0 0 5px 0 rgba(0,0,0,.4);background:white;position:fixed;z-index:10;right:0;left:0;height:30px;text-align:center;overflow-y:scroll;white-space:nowrap}
	.menu button{outline:none;border:0;height:100%;width:fit-content;background:transparent;width:30px;padding:0;}
	.menu button.big{width:60px}
	.menu.shown{bottom:0;}
	#textEditor{height:calc(100% - 70px)}
}
.skin{margin:5px;padding:10px;border-radius:5px;}
#themeMain{text-align:center;}
.themeBox{width:300px;background:#f5f5f5;padding:5px;display:inline-block;border-radius:5px;margin:5px;}
.themeBox img{width:100%;height:200px;object-fit:cover;border-radius:5px;}
.themeBox div,.themeBox span{display:block;width:100%;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;text-align:left;}
.themeBox div{font-size:1.2em;}
.themeBox span{opacity:.6;}
.themeBox.selected{background:#1e9fff;color:white;}
.themeBox.selected div::before{content:"✓ "}

/* </style> */
<?php }elseif($_GET["a"]=="js"){header("content-type: text/javascript"); ?>
/* <script> */



/* ==================== 初始化+部分公用函数实现 ==================== */

/* 加载时进行初始化 */
window.onload = function() {

	/* 如果url后面有设定目录，就按目录来，否则默认打开根目录，主要用于提升使用中refres页面后的体验 */
	if (location.href.split("#")[1]) {
		newdirn = location.href.split("#")[1];
		if (newdirn.split("")[0] != "/") {
			newdirn = "/" + newdirn;
		}
		if (newdirn.split("")[newdirn.split("").length - 1] != "/") {
			newdirn = newdirn + "/";
		}
		dirOperating = newdirn;
	} else {
		dirOperating = "/";
	}

	/* 设定一些需要的变量 */
	forwardFromConfirm = false;
	fileHoverSelecting = false;
	uploadNotFinished = false;
	moveOrCopyMode = null;

	/* 检查当前是否登录状态 */
	request("check", null, function() {
		loadFileList(dirOperating, true);
		history.replaceState({
			"mode": "fileList",
			"dir": dirOperating
		}, document.title)
	});
	
	/* 设置文件拖过事件 */
    document.documentElement.ondragover=function(){
        if($(".files.shown")){
            ID("filesUploadInputContainer").style.display="block";
        }
    };
    
	/* 在首次用非Chromium浏览器访问时弹出兼容性提示(官网和视频都明确说明仅兼容Chromium，别的浏览器没试过) */
	if (navigator.userAgent.indexOf("Chrome") == -1 && !localStorage.getItem("FileAdmin_Settings_BrowserAlert")) {
		alert("FileAdmin 目前仅兼容 Google Chrome 和 Microsoft Edge 的最新版本，使用其他浏览器访问可能导致未知错误。");
		localStorage.setItem("FileAdmin_Settings_BrowserAlert", "0");
	}

	/* 这个是让浏览器save密码时可以给他一个默认的用户名，否则浏览器会存进去一个“无用户名”，容易被别的密码覆盖掉，用户体验消失 */
	ID("passwordManagerUsername").value = "FileAdmin（" + location.host + "）";

	/* 加载时检察更新，有更新的话版本标识就变蓝+提示 */
	fetch("?a=ver").then(function(d) {
		return d.text()
	}).then(function(d) {
		if (d == "1001") {
			ID("versionNote").innerText = "klik perbarui";
			ID("versionNote").classList.add("active")
		} else {
			ID("versionNote").innerText = d;
		}
	}).catch(function(err) {
		ID("versionNote").innerText = "salah"
	});

	/* 处理用户前进、后退的事件 */
	window.onpopstate = function() {
		if (!forwardFromConfirm) {
			if ($(".texteditor.shown")) {
				if (textEditor.getValue() != lastSaveContent && !confirm("Anda memiliki konten yang belum disimpan, apakah Anda benar-benar ingin keluar?")) {
					forwardFromConfirm = true;
					history.forward();
					return;
				}
			}
			if ($(".upload.shown") && uploadNotFinished) {
				history.forward()
			} else {
				let state = event.state;
				if (state && state.mode) {
					let mode = state.mode;
					if (mode == "fileList") {
						dirOperating = state.dir;
						loadFileList(dirOperating, true)
					} else {
						history.back();
					}
				}
			}
		} else {
			forwardFromConfirm = false;
		}
	};
	/* 初始化颜色设定 */
    if(localStorage.FileAdmin_Settings_Theme_Url && localStorage.FileAdmin_Settings_Theme_Url != ""){
        let styleEle = document.createElement("link");
        styleEle.setAttribute("rel","stylesheet");
        styleEle.setAttribute("id","customStylesheet");
        styleEle.setAttribute("href",localStorage.FileAdmin_Settings_Theme_Url);
        document.body.appendChild(styleEle);
    }
};

/* 绑定键盘快捷键 */
window.onkeydown = function() {
	if (event.keyCode == 191) {
		if ($(".files.shown")) {
			editAddressBar();/* 编辑地址栏 */
		}
		if ($(".login.shown")) {
			event.preventDefault();
			ID("loginPassword").focus();/* 聚焦登录密码 */
		}
	} else if ((event.ctrlKey == true || event.metaKey == true) && event.keyCode == 83) {
		event.preventDefault();
		if ($(".texteditor.shown")) {
			saveFile();/* save文件 */
		}
	} else if (event.keyCode == 27) {
		if ($(".texteditor.shown")) {
			history.back();/* berhenti文本编辑器 */
		} else if ($(".files.shown")) {
			history.back(-1);/* 上级目录 */
		}
	} else if ((event.ctrlKey == true || event.metaKey == true) && event.keyCode == 65) {
		if ($(".files.shown")) {
			event.preventDefault();
			fileSelected = fileListOperating;
			loadFileSelected();/* pilih semua文件 */
		}
	} else if (event.keyCode == 46) {
		if ($(".files.shown")) {
			delFile();/* 删除文件 */
		}
	} else if ((event.ctrlKey == true || event.metaKey == true) && event.keyCode == 67) {
		if ($(".files.shown")) {
			setCopyFiles();/* 复制文件 */
		}
	} else if ((event.ctrlKey == true || event.metaKey == true) && event.keyCode == 88) {
		if ($(".files.shown")) {
			setMoveFiles();/* 剪切文件 */
		}
	} else if ((event.ctrlKey == true || event.metaKey == true) && event.keyCode == 86) {
		if ($(".files.shown")) {
			filePaste();/* 粘贴文件 */
		}
	} else if (event.keyCode == 116) {
		event.preventDefault();
		if ($(".files.shown")) {
			loadFileList(dirOperating, true);/* refres文件列表 */
		}
		if ($(".texteditor.shown")) {
			reloadEditor()/* refres编辑器 */
		}
	} else if (event.keyCode == 113) {
		event.preventDefault();
		if ($(".files.shown") && fileSelected.length==1) {
			renameFile();/* 改名 */
		}
	}
	/* 标题栏星号提示 */
    if ($(".texteditor.shown")) {
		if(textEditor.getValue() == lastSaveContent){
		    document.title = fileEditing + " | FileAdmin";
		}else{
		    document.title = "* " + fileEditing + " | FileAdmin";
		}
	}
};

/* 网络请求函数 */
function request(act, txt, callback) {
	if (txt) {
		fetchBody = "a=" + act + "&pwd=" + encodeURIComponent(localStorage.getItem("FileAdmin_Password")) + "&" + txt;
	} else {
		fetchBody = "a=" + act + "&pwd=" + encodeURIComponent(localStorage.getItem("FileAdmin_Password"));
	}
	fetch('?stamp=' + new Date().getTime(), {
			body: fetchBody,
			method: "POST",
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			}
		})
		.then(res => res.text())
		.then(txt => {
			let parsed = txt.split("||");
			let code = Number(parsed[0]);
			if (code == 1000) {
				showModule("login");
			} else {
				if (parsed[1]) {
					msg = parsed[1];
				} else {
					msg = null;
				}
				if (callback) {
					callback(code, msg, txt);
				}
			}
		})
		.catch(err => {
			alert(err);
		})
}

/* 加法小游戏（根目录/危险操作确认提示） */
function confirmRootDirAccess(txt){
    if(dirOperating=="/"){
        let numx = Math.round(Math.random()*5 + 1);
        let numy = Math.round(Math.random()*5 + 1);
        let answer = numx + numy;
        let userAnswer = prompt(txt+"\nSilakan masukkan hasil perhitungan untuk mengkonfirmasi operasi：" + numx + " + " + numy);
        if(userAnswer && Number(userAnswer) == answer){return true;}
    }else{
        if(confirm(txt)){return true;}
    }
    return false;
}

/* 显示模块函数 */
function showModule(name) {
	document.title = "FileAdmin | 极致文件管理体验";
	hideMenu();
	if ($(".module.shown")) {
		$(".module.shown").classList.remove("shown");
	}
	$(".module[data-module^='" + name + "']").classList.remove("hidden");
	$(".module[data-module^='" + name + "']").classList.add("shown");
	if (name == "login") {
		ID("logoutBtn").style.display = "none";
	} else {
		ID("logoutBtn").style.display = "block";
	}
	if (name != "login" && name != "files" && name != "loading") {
		history.pushState({
			'mode': 'other'
		}, document.title)
	}
	if (name != "texteditor" && name != "loading") {
		document.body.classList.remove("mobileInputAdded");
	}
	hideContextMenu();
}

/* 显示菜单函数 */
function showMenu(name) {
	if ($(".menu.shown")) {
		$(".menu.shown").classList.remove("shown");
	}
	$(".menu[data-menu^='" + name + "']").classList.add("shown");
}

/* 隐藏菜单函数 */
function hideMenu() {
	if ($(".menu.shown")) {
		$(".menu.shown").classList.remove("shown");
	}
}

/* 文件体积格式化 */
function humanSize(num) {
	bytes = num / 102.4;
	if (bytes == 0) {
		return "0.00B";
	}
	var e = Math.floor(Math.log(bytes) / Math.log(1024));
	return (bytes / Math.pow(1024, e)).toFixed(2) + 'KMGTP'.charAt(e) + 'B';
}

/* getElementById简写 */
function ID(id) {
	return document.getElementById(id);
}

/* querySelector简写 */
function $(selector) {
	return document.querySelector(selector);
}


/* ==================== 登录部分 ==================== */

/* 监听登录框键盘动作，如果按下了enter就执行登录 */
function loginCheckEnter(eve) {
	if (eve.keyCode == 13) {
		login()
	}
}

/* 登录函数 */
function login() {
	showModule("loading");
	request("login", "loginPwd=" + ID("loginPassword").value, function(code, msg) {
		if (code == 200) {
			localStorage.setItem("FileAdmin_Password", msg);
			loadFileList(dirOperating, true);
			history.replaceState({
				"mode": "fileList",
				"dir": "/"
			}, document.title)
		} else {
			showModule("login");
			alert("password salah (⊙x⊙;)");
		}
	})
}

/* 右上角退登按钮 */
function logout() {
	if (confirm("Apakah Anda benar-benar ingin keluar？＞﹏＜")) {
		localStorage.setItem("FileAdmin_Password", 0);
		showModule("login");
	}
}



/* ==================== upload文件 ==================== */

/* upload文件输入框改变后进行处理 */
function addFilesToUploads(ele) {
    ID("filesUploadInputContainer").style="";
	waitingToUpload = [];
	waitingToUploadCount = 0;
	Array.from(ele.files).forEach(addFileToUploadArr);
	showModule("upload");
	uploadFileFromList(0);
	ele.value = '';
	uploadNotFinished = true;
}

/* 当检测到粘贴事件后将剪切板内容添加到upload列表（即ctrl+vupload）的实现 */
document.addEventListener('paste', function(event) {
	if ($(".files.shown") && !moveOrCopyMode) {
		var items = event.clipboardData && event.clipboardData.items;
		if (items && items.length) {
			waitingToUpload = [];
			waitingToUploadCount = 0;
			for (var i = 0; i < items.length; i++) {
				if (items[i].type !== '') {
					if (items[i].getAsFile()) {
						addFileToUploadArr(items[i].getAsFile());
					}
				}
			}
			showModule("upload");
			uploadNotFinished = true;
			uploadFileFromList(0);
		}
	}
});

/* 将【文件】添加到待uploadArray的函数 */
function addFileToUploadArr(file) {
	waitingToUpload.push({
		"file": file,
		"dir": dirOperating
	});
	waitingToUploadCount++;
}

/* 目录upload输入框内容变化处理 */
function addDirToUploads(ele) {
	waitingToUpload = [];
	waitingToUploadCount = 0;
	Array.from(ele.files).forEach(addDirToUploadArr);
	showModule("upload");
	uploadFileFromList(0);
	ele.value = '';
}

/* 将【目录】添加到待uploadArray的函数 */
function addDirToUploadArr(file) {
	let relativeDir = file.webkitRelativePath.split("/").slice(0, file.webkitRelativePath.split("/").length - 1).join("/") + "/";
	waitingToUpload.push({
		"file": file,
		"dir": dirOperating + relativeDir
	});
	waitingToUploadCount++;
}

/* 从待uploadArray中的第id个文件发送upload请求的函数 */
function uploadFileFromList(id) {
	lastUploadTime = new Date().getTime();
	lastUploadProgress = 0;
	if (!waitingToUpload[id]) {
		uploadNotFinished = false;
		history.back();
	} else {
		waitingToUploadCount--;
		ID("uploadText-CurrFile").innerText = waitingToUpload[id]["file"]["name"];
		ID("uploadText-Waiting").innerText = waitingToUploadCount;
		ID("uploadText-DestDir").innerText = waitingToUpload[id]["dir"];
		ID("uploadProgressBar").style.display = "none";
		setTimeout(function() {
			ID("uploadProgressBar").style.width = "0%";
			ID("uploadProgressBar").style.display = "block";
		}, 50);
		ID("uploadText-CurrProg").innerText = "0% (menghubungkan...)";
		xhr = new XMLHttpRequest();
		xhr.onload = function() {
		    if(xhr.responseText == "1002"){alert("File dengan nama yang sama sudah ada di direktori，upload gagal ＞︿＜");}
		    else if(xhr.responseText != "200"){alert("Pengunggahan file gagal, harap periksa status jaringan Anda dan server＞︿＜");}
			id++;
			uploadFileFromList(id)
		};
		xhr.onerror = function() {
		    alert("Pengunggahan file gagal, harap periksa status jaringan Anda dan server ＞︿＜");
			id++;
			uploadFileFromList(id)
		};
		xhr.open("POST", "?a=upload&pwd=" + encodeURIComponent(localStorage.getItem("FileAdmin_Password")) + "&dir=" + encodeURIComponent(waitingToUpload[id]["dir"]), true);
		xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
		var fd = new FormData();
		fd.append("file", waitingToUpload[id]["file"]);
		xhr.upload.onprogress = function(eve) {
			loaded = eve.loaded / eve.total;
			percent = Math.round((loaded * 100)) + "%";
			ID("uploadProgressBar").style.width = percent;
			ID("uploadText-CurrProg").innerText = percent + " (" + humanSize(eve.loaded / 10) + " / " + humanSize(eve.total / 10) + ")";
			uploadSpeed = humanSize((eve.loaded - lastUploadProgress) / (new Date().getTime() - lastUploadTime) * 100) + "/S";
			ID("uploadText-CurrSpeed").innerText = uploadSpeed;
			if (percent == "100%") {
				ID("uploadText-CurrProg").innerText = percent + " (loading...)";
			}
			lastUploadTime = new Date().getTime();
			lastUploadProgress = eve.loaded;
		};
		xhr.send(fd);
	}
}




/* ==================== 文件浏览器主体部分 ==================== */

/* 获取当前目录的占用信息 */
function getDiskSpaceInfo() {
	showModule("loading");
	request("space", "name=" + encodeURIComponent(dirOperating), function(c, data, d) {
		if (c == 200) {
			let returnData = d.split("||");
			let total = humanSize(returnData[1] / 10);
			let free = humanSize(returnData[2] / 10);
			let freepercent = Math.round(returnData[2] / returnData[1] * 10000) / 100;
			let used = humanSize(returnData[3] / 10);
			let usedpercent = Math.round(returnData[3] / returnData[1] * 10000) / 100;
			let current = humanSize(returnData[4] / 10);
			let currentpercent = Math.round(returnData[4] / returnData[1] * 10000) / 100;
			if (returnData[1] != 0) {
				alert("Informasi sisa penyimpanan berhasil diperoleh( •̀ ω •́ )✧\n\nTotal ruang disk：" + total + "\nruang disk kosong：" + free + "（dari ruang total" + freepercent + "%）" + "\nruang disk yang digunakan：" + used + "（dari ruang total" + usedpercent + "%）" + "\nPenyimpanan di gunakan：" + current + "（dari ruang total" + currentpercent + "%）");
			} else {
			    /* 某些环境（比如kangle虚拟主机）没法获取总空间，这里进行错误处理 */
				alert("Gagal mendapatkan ruang disk total, lingkungan Anda mungkin tidak mengizinkan operasi ini `(*>﹏<*)′\nDirektori yang sedang dilihat menempati" + current + "ruang disk ( •̀ ω •́ )✧")
			}
			loadFileList(dirOperating, true);
		} else if (c == 1001) {
			alert("Direktori yang sedang Anda lihat tidak ada, mungkin telah dihapus /_ \\")
		} else {
			alert("Terjadi kesalahan yang tidak diketahui /_ \\");
		}
	})
}

/* 从服务器获取文件列表并显示 */
function loadFileList(dir, fromState) {
	fileSelected = [];
	ID("addressBar").innerText = "Direktori akar" + dir.replaceAll("/", " / ");
	showModule("loading");
	request("files", "name=" + dir, function(code, data) {
		if (code == 200) {
			fileListArr = JSON.parse(decodeURIComponent(data));
			fileListOperating = [];
			fileListHtml = "";
			fileListArr.forEach(addToFileListHtml);
			ID("fileList").innerHTML = fileListHtml;
			if (fileListHtml == "") {
				ID("fileList").innerHTML = "<center>Direktori yang diminta kosong ヽ(*。>Д<)o゜</center>"
			}
		} else if (code == "1001") {
			ID("fileList").innerHTML = "<center>Direktori yang diminta tidak ada (ノへ￣、)</center>"
		} else if (code = "1002") {
			ID("fileList").innerHTML = "<center>Format nama direktori salah(ﾟДﾟ*)ﾉ</center>"
		}
		showModule("files");
		showMenu("files-noselect");
    	if (window.offsetBeforeEditing) {
    		scrollTo(0, offsetBeforeEditing);
    		offsetBeforeEditing = null;
    	}
	});
	if (!fromState) {
		history.pushState({
			"mode": "fileList",
			"dir": dir
		}, document.title, "#" + dirOperating)
	}
}

/* 用于forEach时将每个文件添加到文件列表的html中 */
function addToFileListHtml(data) {
	if (data.name != "." && data.name != "..") {
		fileType = data.name.split(".")[data.name.split(".").length - 1].toLowerCase();
		fileListOperating.push(data.name);
		fileListHtml = fileListHtml + `<div class="file" onmouseover="hoverSelect(this)" data-isdir=` + data.dir + ` data-filename="` + data.name + `" onclick="viewFile(this)" oncontextmenu="fileContextMenu(this)">` + getFileIco(fileType, data.dir) + ` <div class="fileName">` + data.name + `</div> <div class="size">` + humanSize(data.size * 102.4) + `</div></div>`;
	}
}

/* 用于按照文件类型获取文件图标的html，在搜索文件的列表显示中也用到这个 */
function getFileIco(type, dir) {
	if (dir) {
		return `<svg style='padding:2px' viewBox="0 0 16 16" version="1.1" class="fileIco" fill="#1e9fff"><path d="M1.75 1A1.75 1.75 0 000 2.75v10.5C0 14.216.784 15 1.75 15h12.5A1.75 1.75 0 0016 13.25v-8.5A1.75 1.75 0 0014.25 3H7.5a.25.25 0 01-.2-.1l-.9-1.2C6.07 1.26 5.55 1 5 1H1.75z"></path></svg>`;
	} else if (type == "fajs") {
		return `<img class="fileIco" src="https://asset.simsoft.top/products/fileadmin/filetype/lock.svg">`;
	} else if (["html", "htm", "php", "js", "css", "xml", "json", "xaml"].indexOf(type) != -1) {
		return `<img class="fileIco" src="https://asset.simsoft.top/products/fileadmin/filetype/code.svg">`;
	} else if (["mp3", "wav", "aac", "mid"].indexOf(type) != -1) {
		return `<img class="fileIco" src="https://asset.simsoft.top/products/fileadmin/filetype/audio.svg">`;
	} else if (["png", "ico", "svg", "jpg", "jpeg", "gif", "webp"].indexOf(type) != -1) {
		return `<img class="fileIco" src="https://asset.simsoft.top/products/fileadmin/filetype/image.svg">`;
	} else if (["txt", "md", "yml", "log", "ini"].indexOf(type) != -1) {
		return `<img class="fileIco" src="https://asset.simsoft.top/products/fileadmin/filetype/text.svg">`;
	} else {
		return `<img class="fileIco" src="https://asset.simsoft.top/products/fileadmin/filetype/unknown.svg">`
	}
}

/* 用于编辑文件地址栏（文件列表顶部的那个）的函数 */
function editAddressBar() {
	let newDir = prompt("Silakan masukkan jalur yang ingin Anda tuju (o゜▽゜)o☆", dirOperating);
	if (newDir) {
		if (newDir.split("")[0] != "/") {
			newDir = "/" + newDir;
		}
		if (newDir.split("")[newDir.split("").length - 1] != "/") {
			newDir = newDir + "/";
		}
		dirOperating = newDir;
		loadFileList(dirOperating);
	}
}

/* 当鼠标在文件列表开始拖动时，开始进行快速多选操作 */
function startHoverSelect(ele) {
	if (event.target.getAttribute("data-filename")) {
		fileName = event.target.getAttribute("data-filename")
	} else {
		fileName = event.target.parentNode.getAttribute("data-filename")
	}
	if (fileSelected.indexOf(fileName) == -1) {
		fileHoverSelecting = "select";
	} else {
		fileHoverSelecting = "unselect";
	}
}

/* 当鼠标经过文件列表上方即触发，如果此时正在进行快速多选，则选中鼠标经过的文件，否则啥也不干 */
function hoverSelect(ele) {
	fileName = ele.getAttribute("data-filename");
	if (fileHoverSelecting) {
		if (fileHoverSelecting == "select") {
			if (fileSelected.indexOf(fileName) == -1) {
				fileSelected.push(fileName);
				loadFileSelected();
			}
		} else {
			fileSelected = fileSelected.filter(item => item !== fileName);
			loadFileSelected();
		}
	}
}

/* 处理点击文件后打开文件及选择的操作 */
function viewFile(ele, byname, restoreDirOperating) {
    /* byname就是直接按照文件名打开文件，如果byname是true，则ele应该是一个字符串代表文件名；如果byname是false那就是从输入的元素获取相关信息，ele就是一个html元素 */
	if (!byname) {
		fileIsDir = ele.getAttribute("data-isdir");
		fileName = ele.getAttribute("data-filename");
	} else {
		fileIsDir = false;
		fileName = ele;
	}
	/* 判断一下有没有文件选中，如果选中了文件，则点击操作变为选中或取消选中文件，否则就是打开文件 */
	if (fileSelected.length == 0) {
		offsetBeforeEditing = pageYOffset;
		fileType = fileName.split(".")[fileName.split(".").length - 1].toLowerCase();
		fileEditing = fileName;
		if (fileIsDir == "true") {
			dirOperating = dirOperating + fileName + "/";
			loadFileList(dirOperating);
		} else {
		    /* 这里根据不同的文件类型选择不同的textType，这个type是直接用于选择ace编辑器编辑模式的；如果到最后textType还是null，而且不能用其他查看器打开文件，则会提示fa打不开此文件 */
			textMode = null;
			if (fileType == "html" || fileType == "htm" || fileType == "txt") {
				textMode = "html";
				/* 这个fastinput是移动端下方出现的快速输入按钮，电脑端看不到这东西；下方代码也同理 */
				ID("fastInputHtm").style.display = "block";
				ID("fastInputCss").style.display = "none";
				ID("fastInputJs").style.display = "none";
			} else if (fileType == "php") {
				textMode = "php";
			} else if (fileType == "json") {
				textMode = "json";
			} else if (fileType == "js") {
				textMode = "javascript";
			} else if (fileType == "css") {
				textMode = "css";
				ID("fastInputHtm").style.display = "none";
				ID("fastInputCss").style.display = "block";
				ID("fastInputJs").style.display = "none";
			} else if (fileType == "xml" || fileType == "yml" || fileType == "xaml") {
				textMode = "xml";
			} else if (fileType == "zip") {
			    /* 如果是zip文件则执行解包逻辑 */
				if (confirm("Apakah Anda ingin meng-unzip file ini ~(￣▽￣)~*\nTip: Beberapa lingkungan mungkin tidak mendukung fungsi ini")) {
					let destDir = prompt("Direktori mana yang ingin Anda unzip? (*^▽^*)", dirOperating);
					if (destDir) {
						if (destDir.split("")[0] != "/") {
							destDir = "/" + destDir;
						}
						if (destDir.split("")[destDir.split("").length - 1] != "/") {
							destDir = destDir + "/";
						}
						showModule("loading");
						request("unzip", "name=" + dirOperating + fileName + "&dir=" + destDir, function(code) {
							if (code == 1001) {
								alert("Lingkungan yang Anda gunakan tampaknya tidak mendukung fitur ini")
							} else if (code == 1002) {
								alert("Direktori yang Anda tentukan tidak ada (´。＿。｀)")
							} else if (code == 1003) {
								alert("Tidak dapat menemukan tarbal ini, coba segarkan halaman ini");
							} else {
								alert("Terjadi error yang tidak diketahui, coba segarkan halaman ini");
							}
							loadFileList(dirOperating, true);
						})
					}
				}
			} else if (fileType == "rar" || fileType == "7z") {
			    /* rar和7z不会写，如果有人有现成轮子也可以提交个issue */
				alert("Jenis dekompresi file ini tidak didukung, harap gunakan format .zip");
			} else if (fileType == "jpg" || fileType == "png" || fileType == "jpeg" || fileType == "gif" || fileType == "webp" || fileType == "ico" || fileType == "svg") {
			    /* 图片查看器 */
				showModule("imgviewer");
				showMenu("imgviewer");
				imageViewingUrl = "?a=down&pwd=" + encodeURIComponent(localStorage.getItem("FileAdmin_Password")) + "&name=" + encodeURI(dirOperating + fileName);
				ID("imgviewer").src = imageViewingUrl;
			} else if (fileType == "mp4" || fileType == "webm" || fileType == "mp3") {
			    /* 音视频预览器，反正音视频通用<video>就偷懒了 */
				showModule("vidviewer");
				showMenu("vidviewer");
				vidViewingUrl = "?a=down&pwd=" + encodeURIComponent(localStorage.getItem("FileAdmin_Password")) + "&name=" + encodeURI(dirOperating + fileName);
				ID("vidviewer").src = vidViewingUrl;
			} else if (fileType == "fajs") {
			    /* 直接打开.fajs以后save文件会出问题，生成xxx.fajs.fajs文件，所以不让打开 */
				alert("File .fajs tidak bisa langsung dibuka, silahkan buka file .js dengan nama yang sama~")
			} else {
				if (confirm("Format file ini saat ini tidak didukung pinch..\Apakah Anda ingin mencoba membuka (⊙_⊙)？")) {
					textMode = "html"
				}
			}
			/* 如果有textMode则使用文本编辑器 */
			if (textMode) {
				showModule("loading");
				request("getfile", "name=" + dirOperating + fileName, function(c, d, file) {
					if (fileType == "js") {
						ID("obfuscateBtn").style.display = "inline-block";
						if (localStorage.getItem("FileAdmin_Settings_Obfuscator") == "1") {
							ID("obfuscateBtn").innerText = "matikan kebingungan"
						} else {
							ID("obfuscateBtn").innerText = "mengaktifkan kebingungan"
						}
						ID("fastInputHtm").style.display = "none";
						ID("fastInputCss").style.display = "none";
						ID("fastInputJs").style.display = "block";
					} else {
						ID("obfuscateBtn").style.display = "none"
					}
					if (navigator.maxTouchPoints > 0) {
						document.body.classList.add("mobileInputAdded")
					}
					/* 进行一些ace的相关配置 */
					ace.config.set('basePath', 'https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/');
					textEditor = ace.edit("textEditor");
					textEditor.setOption("enableLiveAutocompletion", true);
					textEditor.setOption("scrollPastEnd",0.5);
					textEditor.session.setValue(file);
					if(localStorage.getItem("FileAdmin_Settings_Theme_Dark")=="true"){textEditor.setTheme("ace/theme/monokai");}else{textEditor.setTheme("ace/theme/chrome");}
					textEditor.gotoLine(1);
					textEditor.setShowPrintMargin(false);
					textEditor.session.setMode("ace/mode/" + textMode);
					loadEditorFontSize();
					/* 显示texteditor的菜单和主体 */
					showModule("texteditor");
					showMenu("texteditor");
					/* 更改页面标题方便用户区分窗口 */
					document.title = fileName + " | FileAdmin";
					lastSaveContent = textEditor.getValue();
				});
			}
		}
	} else {
		if (fileSelected.indexOf(fileName) == -1) {
			fileSelected.push(fileName);
			loadFileSelected();
		} else {
			fileSelected = fileSelected.filter(item => item !== fileName);
			loadFileSelected();
		}
	}
	if (restoreDirOperating) {
		dirOperating = "/";
	}
}

/* 加载编辑器字体大小 */
function loadEditorFontSize(){
    if(localStorage.FileAdmin_Settings_Editor_Font){
        ID("textEditor").style.fontSize=localStorage.FileAdmin_Settings_Editor_Font+"px";
    }else{
        ID("textEditor").style.fontSize="12px";
    }
}

/* 修改编辑器字体大小 */
function editEditorFontSize(){
    if(localStorage.FileAdmin_Settings_Editor_Font){
        currentSet=localStorage.FileAdmin_Settings_Editor_Font;
    }else{
        currentSet="12";
    }
    let newSet=Number(prompt("Silakan masukkan ukuran font yang akan diatur, disarankan antara 12-20(。・∀・)ノ",currentSet));
    if(newSet){
        localStorage.FileAdmin_Settings_Editor_Font=newSet;
        loadEditorFontSize();
    }
}

/* 根据没选文件、选一个文件、选一堆文件显示不同的功能菜单 */
function loadFileMenu() {
	if ($(".files.shown")) {
		if (fileSelected.length == 0) {
			showMenu("files-noselect")
		} else if (fileSelected.length == 1) {
			showMenu("files-singleselect")
		} else {
			showMenu("files-multiselect")
		}
		if (moveOrCopyMode) {
			ID("pasteBtn").style.display = "inline-block"
		} else {
			ID("pasteBtn").style.display = "none"
		}
	}
}

/* 加载选择的文件列表 */
function loadFileSelected() {
	Array.prototype.slice.call(document.getElementsByClassName("file")).forEach(checkFileSelected);
	loadFileMenu();
}

/* 如果输入的ele代表的文件被选中了，则给他classList添加被选中，否则移除 */
function checkFileSelected(ele) {
	if (fileSelected.indexOf(ele.getAttribute("data-filename")) == -1) {
		ele.classList.remove("selected")
	} else {
		ele.classList.add("selected")
	}
}

/* 打包目录 */
function zipCurrentDir() {
	if (confirm("Apakah Anda benar-benar ingin mengemas direktori saat ini sebagai file Zip? (⊙_⊙)？\nTip: Beberapa lingkungan mungkin tidak mendukung fungsi ini")) {
		showModule("loading");
		request("zip", "name=" + encodeURIComponent(dirOperating), function(code) {
			if (code == 1001) {
				alert("Pengemasan file gagal..（＞人＜；）")
			}
			loadFileList(dirOperating, true);
		})
	}
}

/* 创建文件 */
function newFile() {
	let filename = prompt("📄 Masukkan nama file baru (●'◡'●)");
	if (filename) {
		showModule("loading");
		if (filename.indexOf("/") == -1) {
			request("save", "name=" + encodeURIComponent(dirOperating + filename), function(code) {
			    if(code != 200){alert("Pembuatan gagal, coba periksa izin di bawah direktori saat ini ╯︿╰");}
				loadFileList(dirOperating, true)
			});
		} else {
			alert("Nama file tidak boleh berisi karakter khusus (；′⌒`)");
			loadFileList(dirOperating, true)
		}
	}
}

/* 创建目录 */
function newDir() {
	let filename = prompt("📂 Masukkan nama direktori baru (●'◡'●)");
	if (filename) {
		showModule("loading");
		if (filename.indexOf("/") == -1 && filename.indexOf("<") == -1 && filename.indexOf(">") == -1 && filename.indexOf("&") == -1) {
			request("mkdir", "name=" + encodeURIComponent(dirOperating + filename), function(code) {
			    if(code != 200){alert("Pembuatan gagal, coba periksa izin di bawah direktori saat ini ╯︿╰");}
				loadFileList(dirOperating, true)
			});
		} else {
			alert("Nama direktori tidak boleh berisi karakter khusus (；′⌒`)");
			loadFileList(dirOperating, true)
		}
	}
}

/* 打开文件搜索界面 */
function openFileFinder() {
	ID("searchAddrBar").innerText = "Direktori pencarian saat ini:" + ID("addressBar").innerText;
	showModule("search");
	showMenu("search");
	ID("searchResult").innerHTML = '<div style="padding:50px 0;opacity:.5;text-align:center">Anda belum memulai pencarian ㄟ( ▔, ▔ )ㄏ</div>';
	ID("replaceBtn").style.display = "none";
}

/* 重命名文件 */
function renameFile() {
	let newName = prompt("Harap masukkan nama baru untuk file tersebut(*^▽^*)", fileSelected[0]);
	if (newName) {
		if (newName.indexOf("/") == -1 && newName.indexOf("&") == -1) {
			showModule("loading");
			request("rename", "dir=" + encodeURIComponent(dirOperating) + "&old=" + encodeURIComponent(fileSelected[0]) + "&new=" + encodeURIComponent(newName), function(c) {
				if (c == 1002) {
					alert("Dokumen “" + newName + "” sudah ada (；′⌒`)")
				} else if (c != 200) {
					alert("Terjadi kesalahan yang tidak diketahui, harap periksa izin file (；′⌒`)")
				}
				loadFileList(dirOperating, true)
			});
		} else {
			alert("Nama file tidak boleh berisi karakter khusus (；′⌒`)");
			loadFileList(dirOperating, true)
		}
	}
}

/* 下载文件（只支持一个文件，多的用户要先打包再下载） */
function downCurrFile() {
	if ($(".file.selected").getAttribute("data-isdir") == "true") {
		alert("Tidak mendukung unduhan folder unduhan langsung..")
	} else {
		downUrl = "?a=down&pwd=" + encodeURIComponent(localStorage.getItem("FileAdmin_Password")) + "&name=" + encodeURI(dirOperating + fileSelected[0]);
		location = downUrl;
	}
}

/* 删除 */
function delFile() {
	let fileDelStr = JSON.stringify(fileSelected);
	if (confirmRootDirAccess("Apakah Anda yakin ingin menghapus file dan direktori yang dipilih secara permanen (⊙_⊙)？")) {
		showModule("loading");
		request("del", "files=" + encodeURIComponent(fileDelStr) + "&dir=" + dirOperating, function(code) {
			if(code != 200){alert("Terjadi masalah saat menghapus beberapa file, harap periksa izin file ＞﹏＜");}
			loadFileList(dirOperating, true);
		});
	}
}

/* “剪切”按钮处理，记录等会要进行的操作是剪切，以及要剪切的文件是哪些 */
function setMoveFiles() {
	moveOrCopyMode = "move";
	moveOrCopyFromDir = dirOperating;
	moveOrCopyFiles = JSON.stringify(fileSelected);
	fileSelected = [];
	loadFileSelected();
}

/* “复制”按钮处理，记录等会要进行的操作是复制，以及要复制的文件是哪些 */
function setCopyFiles() {
	moveOrCopyMode = "copy";
	moveOrCopyFromDir = dirOperating;
	moveOrCopyFiles = JSON.stringify(fileSelected);
	fileSelected = [];
	loadFileSelected();
}

/* 粘贴文件时post 要进行的操作&要对他进行操作的文件&目标目录 给服务器进行处理 */
function filePaste() {
	if (moveOrCopyMode) {
		showModule("loading");
		request(moveOrCopyMode, "files=" + moveOrCopyFiles + "&from=" + moveOrCopyFromDir + "&to=" + dirOperating, function() {
			loadFileList(dirOperating, true);
		});
		moveOrCopyMode = null;
		ID("pasteBtn").style.display = "none";
	}
}




/* ==================== 文本编辑器部分 ==================== */

/* save文件 */
function saveFile(forceDisableObfuscator) {
	textEditor.focus();
	ID("saveMenuText").innerText = "······";
	ID("loadingAnimations").classList.add("shown");
	if (!forceDisableObfuscator && fileEditing.split(".")[fileEditing.split(".").length - 1].toLowerCase() == "js" && localStorage.getItem("FileAdmin_Settings_Obfuscator") == "1") {
		try {
			let obfuscated = JavaScriptObfuscator.obfuscate(textEditor.getValue(), {
				compact: true,
				controlFlowFlattening: true,
				controlFlowFlatteningThreshold: 1,
				numbersToExpressions: true,
				simplify: true,
				stringArrayShuffle: true,
				splitStrings: true,
				stringArrayThreshold: 1
			})._obfuscatedCode;
			request("fajssave", "name=" + dirOperating + fileEditing + "&original=" + encodeURIComponent(textEditor.getValue()) + "&obfuscate=" + encodeURIComponent(obfuscated), function(code) {
				ID("loadingAnimations").classList.remove("shown");
				if (code == 200) {
					lastSaveContent = textEditor.getValue();
					ID("saveMenuText").innerText = "Menyelesaikan";
					document.title = fileEditing + " | FileAdmin";
					setTimeout(function() {
						ID("saveMenuText").innerHTML = "save";
					}, 700)
				} else {
					alert("Terjadi kesalahan yang tidak diketahui, harap periksa koneksi jaringan dan izin file Anda");
					ID("saveMenuText").innerHTML = "save";
				}
			})
		} catch (err) {
			if(confirm("Ada kesalahan di obfuscator, apakah Anda ingin menyimpan kode asli untuk Anda? `(*>﹏<*)′\n\n" + err + "\n\nSilakan periksa kode untuk kesalahan~")){
			    saveFile(true);
			}else{
			    ID("saveMenuText").innerHTML = "save";
			    ID("loadingAnimations").classList.remove("shown");
			}
		}
	} else {
		request("save", "name=" + dirOperating + fileEditing + "&data=" + encodeURIComponent(textEditor.getValue()), function(code) {
			ID("loadingAnimations").classList.remove("shown");
			if (code == 200) {
				lastSaveContent = textEditor.getValue();
				ID("saveMenuText").innerText = "Menyelesaikan";
				document.title = fileEditing + " | FileAdmin";
				setTimeout(function() {
					ID("saveMenuText").innerHTML = "save";
				}, 700)
			} else {
				alert("Terjadi kesalahan yang tidak diketahui, harap periksa koneksi jaringan dan izin file Anda");
				ID("saveMenuText").innerHTML = "save";
			}
		})
	}
}

/* 设置自动garis baru方式 */
function setWrap(ele) {
	if (textEditor.getSession().getUseWrapMode() == true) {
		textEditor.getSession().setUseWrapMode(false);
		ele.innerText = "penutup";
		setTimeout(function() {
			ele.innerText = "garis baru"
		}, 700)
	} else {
		textEditor.getSession().setUseWrapMode(true);
		ele.innerText = "memungkinkan";
		setTimeout(function() {
			ele.innerText = "garis baru"
		}, 700)
	}
}

/* 设置js是否进行混淆 */
function setObfuscate() {
	if (localStorage.getItem("FileAdmin_Settings_Obfuscator") == "1") {
		localStorage.setItem("FileAdmin_Settings_Obfuscator", "0");
		ID('obfuscateBtn').innerText = "mengaktifkan kebingungan"
	} else {
		if (confirm("Sebelum mengaktifkan penyamaran Js, harap baca petunjuk berikut dengan cermat:\n\n- Penyamaran Js dapat secara efektif mencegah orang lain mencuri kode sumber Js Anda\n- Penyamaran Js akan menggandakan penggunaan penyimpanan file Js Anda\n- Penyamaran Js mungkin Beberapa kode tidak dapat jalankan\n- Mungkin sulit bagi Anda untuk men-debug kode Js yang di-obfuscate\n- Setelah obfuscation Js diaktifkan, file .fajs akan dibuat di direktori saat ini untuk menyimpan file sumber Js\n- Pastikan untuk menggunakan firewall untuk melindungi orang lain Akses ke file .fajs\n- Jangan mengubah, memindahkan, atau menghapus file .fajs secara langsung\n\nUntuk petunjuk selengkapnya, lihat beranda proyek Github. Apakah Anda masih perlu mengaktifkan fungsi obfuscation Js?")) {
			localStorage.setItem("FileAdmin_Settings_Obfuscator", "1");
			ID("obfuscateBtn").innerText = "matikan kebingungan"
		}
	}
}

/* 重载编辑器和文件 */
function reloadEditor() {
	if (textEditor.getValue() != lastSaveContent) {
		if (confirm("Anda memiliki konten yang belum disimpan, apakah Anda benar-benar ingin menyegarkan?")) {
			viewFile(fileEditing, true)
		}
	} else {
		viewFile(fileEditing, true)
	}
}



/* ==================== PC右键菜单 ==================== */

/* 显示右键菜单 */
function showContextMenu() {
	if (navigator.maxTouchPoints == 0) {
		hideContextMenu();
		if ($(".menu.shown")) {
			event.preventDefault();
			let menuElem = document.createElement("contextmenu");
			menuElem.innerHTML = $(".menu.shown").innerHTML;
			menuElem.onmousedown = function() {
				event.stopPropagation();
			};
			menuElem.onclick = function() {
				event.stopPropagation();
				hideContextMenu();
			};
			menuElem.style.top = event.clientY + "px";
			menuElem.style.left = event.clientX + "px";
			if (event.clientX > document.getElementsByTagName("html")[0].clientWidth - 150) {
				menuElem.style.left = event.clientX - 150 + "px";
			}
			document.body.appendChild(menuElem);
		}
	}
}

/* 隐藏右键菜单 */
function hideContextMenu() {
	if ($("contextmenu")) {
		$("contextmenu").remove()
	}
}

/* 在文件列表右键的事件处理 根据选择文件数判断只弹菜单还是选中+弹菜单 */
function fileContextMenu(ele) {
	if (fileSelected.length < 2) {
		event.stopPropagation();
		navigator.vibrate([100]);
		fileSelected = [ele.getAttribute("data-filename")];
		loadFileSelected();
		showContextMenu();
	} else {
		showContextMenu();
	}
}




/* ==================== 搜索器部分 ==================== */

/* post搜索文件请求 */
function startSearch() {
	showModule("loading");
	if (ID("searchMode").value == "1") {
		request("find_by_name", "type=" + encodeURIComponent(ID("searchType").value) + "&find=" + encodeURIComponent(ID("searchContent").value) + "&case=" + encodeURIComponent(ID("searchCase").value) + "&dir=" + encodeURIComponent(searchDir), function(c, d) {
			searchedArr = JSON.parse(decodeURIComponent(d));
			searchResultHtml = "";
			searchedArr.forEach(addToSearchResultHtml);
			showModule("search");
			showMenu("search");
			ID("searchResult").innerHTML = searchResultHtml;
			if (searchResultHtml == "") {
				ID("searchResult").innerHTML = '<div style="padding:50px 0;opacity:.5;text-align:center">Tidak ditemukan file yang cocok ㄟ( ▔, ▔ )ㄏ</div>';
			}
		})
	} else {
		request("find_by_content", "type=" + encodeURIComponent(ID("searchType").value) + "&find=" + encodeURIComponent(ID("searchContent").value) + "&case=" + encodeURIComponent(ID("searchCase").value) + "&dir=" + encodeURIComponent(searchDir), function(c, d) {
			searchedArr = JSON.parse(decodeURIComponent(d));
			searchResultHtml = "";
			searchedArr.forEach(addToSearchResultHtml);
			showModule("search");
			showMenu("search");
			ID("searchResult").innerHTML = searchResultHtml;
			if (ID("searchMode").value == "3") {
				ID("replaceBtn").style.display = "inline-block"
			}
			if (searchResultHtml == "") {
				ID("searchResult").innerHTML = '<div style="padding:50px 0;opacity:.5;text-align:center">Tidak ditemukan file yang cocok ㄟ( ▔, ▔ )ㄏ</div>';
				ID("replaceBtn").style.display = "none"
			}
		})
	}
}

/* 将搜到的东西添加到搜索结果html中 */
function addToSearchResultHtml(data) {
	fileType = data.split(".")[data.split(".").length - 1].toLowerCase();
	searchResultHtml = searchResultHtml + `<div class="file" data-filename="` + data.replace("//", "/") + `" onclick='viewFile("` + data.replace("//", "/") + `",true,true)'>` + getFileIco(fileType, false) + `	<div class="fileName">` + data.replace("//", "/") + `</div>	</div>`;
}

/* 根据不同的搜索模式显示不同的功能 */
function loadSearchMode(ele) {
	if (ele.value == "3") {
		ID("replaceOptnInput").style.display = "block";
		ID("replaceHidden").style.display = "none";
		ID("searchCase").value = "1"
	} else {
		ID("replaceOptnInput").style.display = "none";
		ID("replaceBtn").style.display = "none";
		ID("replaceHidden").style.display = "block"
	}
}

/* 在点击替换时显示警告后发送请求 */
function startChange() {
	if (confirm("Operasi penggantian berbahaya dan tidak mendukung undo. Sangat disarankan untuk memeriksa daftar file dengan hati-hati dan mengemas seluruh direktori untuk pencadangan sebelum dijalankan. Apakah anda yakin ingin melanjutkan (⊙_⊙)？")) {
		showModule("loading");
		request("replace", "type=" + encodeURIComponent(ID("searchType").value) + "&find=" + encodeURIComponent(ID("searchContent").value) + "&replace=" + encodeURIComponent(ID("searchReplaceContent").value) + "&dir=" + encodeURIComponent(searchDir), function(c, d) {
			alert("在" + d + "Menyelesaikan operasi yang diganti dalam file (*^▽^*)");
			openFileFinder();
		})
	}
}



/* ==================== 移动端符号输入器 ==================== */

/* 移动输入器点击后插入相应文本 */
function mobileInput(ele) {
	textEditor.insert(ele.innerText);
	textEditor.focus();
}

/* 输入器前箭头按钮处理 */
function mobileEditorPrevious() {
	currentLine = textEditor.selection.getCursor().row + 1;
	currentChar = textEditor.selection.getCursor().column;
	textEditor.gotoLine(currentLine, currentChar - 1);
	textEditor.focus();
}

/* 输入器后箭头按钮处理 */
function mobileEditorNext() {
	currentLine = textEditor.selection.getCursor().row + 1;
	currentChar = textEditor.selection.getCursor().column;
	textEditor.gotoLine(currentLine, currentChar + 1);
	textEditor.focus();
}

/* 输入器切换语言模式 */
function changeMobileInputMode(id) {
	ID("fastInputHtm").style.display = "none";
	ID("fastInputCss").style.display = "none";
	ID("fastInputJs").style.display = "none";
	ID("fastInput" + id).style.display = "block";
	textEditor.focus();
}



/* ==================== 主题切换 ==================== */
function loadThemeList(){
    showModule("loading");
    fetch("https://fa.nlrdev.top/styles/api.php?stamp="+ new Date().getTime()).then(function(d){return d.json()}).then(function(d){
        ID("themeMain").innerHTML='';
        d.forEach(function(theme){
            ID("themeMain").innerHTML+=`
                <div class="themeBox" onclick="selectTheme('${theme.name}','${theme.link}','${theme.dark}')" data-theme="${theme.name}">
                    <img src="${theme.preview}">
                    <div>${theme.name}</div>
                    <span>${theme.author}</span>
                </div>
            `;
        });
        showModule("skin");
        showMenu("skin");
        if(!localStorage.FileAdmin_Settings_Theme_Name){localStorage.FileAdmin_Settings_Theme_Name="Autentik";}
        if($(".themeBox[data-theme^='"+localStorage.FileAdmin_Settings_Theme_Name+"']")){$(".themeBox[data-theme^='"+localStorage.FileAdmin_Settings_Theme_Name+"']").classList.add("selected");}
    }).catch(function(err){
        alert("Terjadi masalah saat menghubungkan ke sistem tema, harap periksa koneksi jaringan perangkat dan pastikan tidak sedang digunakan VPN。\n"+err);
        history.back(-1);
    });
}
function selectTheme(name,link,dark){
    localStorage.setItem("FileAdmin_Settings_Theme_Name",name);
    localStorage.setItem("FileAdmin_Settings_Theme_Url",link);
    localStorage.setItem("FileAdmin_Settings_Theme_Dark",dark);
    loadThemeList();
    if(ID("customStylesheet")){ID("customStylesheet").remove();}
    if(localStorage.FileAdmin_Settings_Theme_Url != ""){
        let styleEle = document.createElement("link");
        styleEle.setAttribute("rel","stylesheet");
        styleEle.setAttribute("id","customStylesheet");
        styleEle.setAttribute("href",localStorage.FileAdmin_Settings_Theme_Url);
        document.body.appendChild(styleEle);
    }
}


/* ==================== 本体更新 ==================== */

/* 检查更新 */
function chkupd() {
	showModule("loading");
	request("chkupd", null, function(c, d, o) {
		if (o == "1001") {
			alert("FileAdmin Anda adalah versi terbaru~");
			loadFileList(dirOperating, true)
		} else if (o == "1002") {
			alert("Gagal mendapatkan pembaruan, lingkungan jaringan server Anda mungkin tidak dapat mengakses server API Hydrosoft (；′⌒`)");
			loadFileList(dirOperating, true)
		} else {
			showModule("updinfo");
			showMenu("updinfo");
			ID("updinfo").innerHTML = o
		}
	})
}

/* 应用更新 */
function applupd() {
	showModule("loading");
	request("applyversion", null, function(c) {
		if (c == 200) {
			location.reload();
		} else {
			alert("pembaharuan gagal..");
			history.back();
			showMenu("updinfo")
		}
	})
}

//</script><?php }else{ ?>
<!DOCTYPE html>
<html onmousedown="hideContextMenu()" oncontextmenu="showContextMenu()" onclick="if(!fileHoverSelecting){fileSelected=[];loadFileSelected();}" onmouseup="setTimeout(function(){fileHoverSelecting=false;},50)">
	<head>
		<title>FileAdmin</title>
		<meta name="viewport" content="width=device-width">
		<link rel="icon" href="//asset.simsoft.top/branding/projects/fileadmin.png">
		<link rel="stylesheet" href="?a=css">
		<link rel="stylesheet" href="path/to/font-awesome/css/font-awesome.min.css">
	</head>
	<body>
		<div class="title">
			<div class="appName" onclick="chkupd()">File<b>Admin</b><div id="versionNote">mengambil</div></div>
			<svg id="skinBtn" onclick="loadThemeList()" width="20" height="20" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M37 17V37M11 37V44H37V37M11 37H4V17C4 14 6 10.5 9 8C12 5.5 18 4 18 4H30C30 4 36 5.5 39 8C42 10.5 44 14 44 17V37H37M11 37V17" stroke="#333" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M30 4C30 7.31371 27.3137 10 24 10C20.6863 10 18 7.31371 18 4" stroke="#333" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<svg id="logoutBtn" onclick="logout()" width="20" height="20" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="48" height="48" fill="white" fill-opacity="0.01"/><path d="M23.9917 6L6 6L6 42H24" stroke="#000000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M33 33L42 24L33 15" stroke="#000000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 23.9917H42" stroke="#000000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
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
			<div class="loginTitle">Gabung FileAdmin</div>
			<input id="passwordManagerUsername">
			<input autofocus id="loginPassword" placeholder="silakan masukkan kata sandi (/▽＼)" type="password" onkeydown="loginCheckEnter(event)"><button onclick="login()" class="loginBtn">→</button>
		</div>
		
		<!--文件列表页-->
		<div class="module files" data-module="files">
			<div class="addressBar"><button title="Direktori akar" onclick="dirOperating='/';loadFileList('/')">/</button><button title="kembali" onclick="history.back(-1)"><</button><div id="addressBar" onclick="editAddressBar()" oncontextmenu="event.stopPropagation();event.preventDefault();navigator.clipboard.writeText(dirOperating);alert('Jalur saat ini telah disalin ke clipboard ( •̀ ω •́ )✧')">/</div></div>
			<br><div id="fileList" onclick="event.stopPropagation();" onmousedown="if(event.button==0){startHoverSelect(this)}"></div>
		</div>
		<div class="menu" data-menu="files-noselect" onclick="event.stopPropagation();">
			<button onclick="fileSelected=fileListOperating;loadFileSelected();">pilih semua<contextmenukey>Ctrl + A</contextmenukey></button>
			<button onclick="loadFileList(dirOperating,true)">refres<contextmenukey>F5</contextmenukey></button>
			<button onclick="showMenu('files-upload')">upload</button>
			<button onclick="zipCurrentDir()">kompres</button>
			<button onclick="showMenu('files-newfile')">buat file baru</button>
			<button onclick="openFileFinder();searchDir=dirOperating;dirOperating=''" class="big">cari file</button>
			<button onclick="getDiskSpaceInfo()" class="big">info disk</button>
			<button onclick="filePaste()" id="pasteBtn" style="display:none">tempel<contextmenukey>Ctrl + V</contextmenukey></button>
		</div>
		<div class="menu" data-menu="files-singleselect" onclick="event.stopPropagation();">
			<button onclick="fileSelected=fileListOperating;loadFileSelected();">pilih semua<contextmenukey>Ctrl + A</contextmenukey></button>
			<button onclick="fileSelected=[];loadFileSelected();" class="big">hapus centang</button>
			<button onclick="renameFile();">ganti Nama<contextmenukey>F2</contextmenukey></button>
			<button onclick="downCurrFile();">unduh</button>
			<button onclick="setMoveFiles();">untuk memotong<contextmenukey>Ctrl + X</contextmenukey></button>
			<button onclick="setCopyFiles();">menyalin<contextmenukey>Ctrl + C</contextmenukey></button>
			<button onclick="delFile();">menghapus<contextmenukey>Delete</contextmenukey></button>
		</div>
		<div class="menu" data-menu="files-multiselect" onclick="event.stopPropagation();">
			<button onclick="fileSelected=fileListOperating;loadFileSelected();">pilih semua<contextmenukey>Ctrl + A</contextmenukey></button>
			<button onclick="fileSelected=[];loadFileSelected();" class="big">hapus centang</button>
			<button onclick="setMoveFiles();">untuk memotong<contextmenukey>Ctrl + X</contextmenukey></button>
			<button onclick="setCopyFiles();">menyalin<contextmenukey>Ctrl + C</contextmenukey></button>
			<button onclick="delFile();">menghapus<contextmenukey>Delete</contextmenukey></button>
		</div>
		<div class="menu" data-menu="files-upload">
			<button class="big" onclick="ID('filesUploadInput').click()">unggah file</button>
			<button class="big" onclick="ID('folderUploadInput').click()">unggah direktori</button>
			<button onclick="loadFileMenu();">Membatalkan</button>
		</div>
		<div class="menu" data-menu="files-newfile">
			<button onclick="newDir()" class="big">direktori baru</button>
			<button onclick="newFile()" class="big">buat file baru</button>
			<button onclick="loadFileMenu();">Membatalkan</button>
		</div>
		
		<!--文件upload器-->
		<div class="module upload" data-module="upload">
			<div style="font-size:1.5em;text-align:center;">mengunggah ψ(._. )></div>
			<div class="uploadProgress"><div id="uploadProgressBar"></div></div>
			<div class="uploadText">Unggahan saat ini:<span id="uploadText-CurrFile"></span></div>
			<div class="uploadText">Kemajuan saat ini:<span id="uploadText-CurrProg"></span></div>
			<div class="uploadText">Kecepatan saat ini:<span id="uploadText-CurrSpeed"></span></div>
			<div class="uploadText">Direktori target: direktori root<span id="uploadText-DestDir"></span></div>
			<div class="uploadText">Menunggu unggahan:<span id="uploadText-Waiting"></span> file</div>
		</div>
		
		<!--纯文本编辑器-->
		<div class="module texteditor" data-module="texteditor">
			<div id="textEditor"></div>
		</div>
		<div id="mobileFastInput">
			<div id="fastInputHtm">
				<div class="mobileInputBtn" onclick="mobileInput(this)"><</div>
				<div class="mobileInputBtn" onclick="mobileInput(this)">></div>
				<div class="mobileInputBtn" onclick="mobileInput(this)">"</div>
				<div class="mobileInputBtn" onclick="mobileInput(this)">'</div>
				<div class="mobileInputBtn" onclick="mobileInput(this)">=</div>
				<div class="mobileInputBtn" onclick="mobileEditorPrevious()">←</div>
				<div class="mobileInputBtn" onclick="mobileEditorNext()">→</div>
				<div class="mobileInputBtn mode" onclick="changeMobileInputMode('Js')">HTM</div>
			</div>
			<div id="fastInputJs" style="display:none">
				<div class="mobileInputBtn" onclick="mobileInput(this)">{</div>
				<div class="mobileInputBtn" onclick="mobileInput(this)">}</div>
				<div class="mobileInputBtn" onclick="mobileInput(this)">(</div>
				<div class="mobileInputBtn" onclick="mobileInput(this)">)</div>
				<div class="mobileInputBtn" onclick="mobileInput(this)">"</div>
				<div class="mobileInputBtn" onclick="mobileInput(this)">'</div>
				<div class="mobileInputBtn" onclick="mobileInput(this)">;</div>
				<div class="mobileInputBtn" onclick="mobileInput(this)">=</div>
				<div class="mobileInputBtn" onclick="mobileEditorPrevious()">←</div>
				<div class="mobileInputBtn" onclick="mobileEditorNext()">→</div>
				<div class="mobileInputBtn mode" onclick="changeMobileInputMode('Css')">JS</div>
			</div>
			<div id="fastInputCss" style="display:none">
				<div class="mobileInputBtn" onclick="mobileInput(this)">{</div>
				<div class="mobileInputBtn" onclick="mobileInput(this)">}</div>
				<div class="mobileInputBtn" onclick="mobileInput(this)">#</div>
				<div class="mobileInputBtn" onclick="mobileInput(this)">%</div>
				<div class="mobileInputBtn" onclick="mobileInput(this)">:</div>
				<div class="mobileInputBtn" onclick="mobileInput(this)">;</div>
				<div class="mobileInputBtn" onclick="mobileEditorPrevious()">←</div>
				<div class="mobileInputBtn" onclick="mobileEditorNext()">→</div>
				<div class="mobileInputBtn mode" onclick="changeMobileInputMode('Htm')">CSS</div>
			</div>
		</div>
		<div class="menu" data-menu="texteditor">
			<button onclick="setObfuscate()" id="obfuscateBtn" class="big"></button>
			<button onclick="saveFile()" id="saveBtn"><span id="saveMenuText">save</span><span id="saveContextMenuText">save</span><contextmenukey>Ctrl + S</contextmenukey></button>
			<button onclick="reloadEditor()">refres<contextmenukey>F5</contextmenukey></button>
			<button onclick="setWrap(this)">garis baru</button>
			<button onclick="editEditorFontSize()">font</button>
			<button onclick="window.open('.'+dirOperating+fileEditing)">pratinjau</button>
			<button onclick="history.back()">kembali<contextmenukey>ESC</contextmenukey></button>
		</div>
		<!--图片预览器-->
		<div class="module imgviewer" data-module="imgviewer"><img id="imgviewer"></div>
		<div class="menu" data-menu="imgviewer">
			<button onclick="location=imageViewingUrl" class="big">unduh gambar</button>
			<button onclick="ID('imgviewer').src='';history.back();">kembali</button>
		</div>
		<!--视频播放器-->
		<div class="module vidviewer" data-module="vidviewer"><video controls id="vidviewer" autoplay></video></div>
		<div class="menu" data-menu="vidviewer">
			<button onclick="location=vidViewingUrl" class="big">Unduh video</button>
			<button onclick="ID('vidviewer').src='';history.back();">kembali</button>
		</div>
		
		<!--重量级文件搜索器-->
		<div class="module search" data-module="search">
			<div class="addressBar" id="searchAddrBar"></div><br>
			<div id="searchOptnArea" style="padding:10px">
				<div><span>menemukan konten</span><input id="searchContent" autocomplete="off" placeholder="Masukkan nama file/isi file yang akan dicari q(≧▽≦q)"></div>
				<div><span>format pencarian</span><input value="html php css js" id="searchType" autocomplete="off" placeholder="Ruang dipisahkan, biarkan kosong untuk menemukan semua file ( •̀ ω •́ )✧"></div>
				<div id="replaceOptnInput" style="display:none"><span>mengganti konten</span><input id="searchReplaceContent" placeholder="Masukkan konten file yang akan diganti §(*￣▽￣*)§"></div>
				<div><span>Modus operasi</span><select id="searchMode" onchange="loadSearchMode(this)"><option value="1">hanya cocok dengan nama file</option><option value="2">mencocokkan konten file</option><option value="3">Temukan dan ganti konten file</option></select></div>
				<div id="replaceHidden"><span>file tersembunyi</span><select id="searchCase"><option value="1">menyalakan</option><option value="2">penutup</option></select></div>
			</div><br>
			<div id="searchResult"></div>
		</div>
		<div class="menu" data-menu="search">
			<button onclick="startSearch()" class="big">mulai mencari</button>
			<button onclick="startChange()" style="display:none" class="big" id="replaceBtn">konfirmasi penggantian</button>
			<button onclick="dirOperating='/';history.back();">berhenti</button>
		</div>
			
		<!--更新信息-->
		<div class="module updinfo" data-module="updinfo">
			<div style="font-size:1.5em;text-align:center;padding:10px;">pembaruan terdeteksi</div>
			<div id="updinfo"></div>
		</div>
		<div class="menu" data-menu="updinfo">
			<button onclick="applupd()" class="big">pembaruan aplikasi</button>
			<button onclick="history.back()">Membatalkan</button>
		</div>
		
		<!--主题样式-->
		<div class="module skin" data-module="skin">
			<div style="font-size:1.5em;text-align:center;padding:10px;">gaya tema</div>
			<div id="themeMain"></div>
		</div>
		<div class="menu" data-menu="skin">
			<button onclick="history.back();">save</button>
		</div>
		
		<input type="file" style="display:none" multiple webkitdirectory id="folderUploadInput" onchange="addDirToUploads(this)">
		<div id="filesUploadInputContainer" ondragleave="this.style=''">
		    <div><span>(•ω•`)</span>drop untuk upload<br>unggah banyak</div>
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
