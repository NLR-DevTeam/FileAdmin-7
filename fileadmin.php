<?php $PASSWORD="TYPE-YOUR-PASSWORD-HERE"; $VERSION=6.026;

    /* SimSoft FileAdmin       © SimSoft, All rights reserved. */
    /*请勿将包含此处的截图发给他人，否则其将可以登录FileAdmin！*/
    
	error_reporting(0);
	function scandirAll($dir,$first=false){	
		$files = [];
		$child_dirs = scandir($dir);
		foreach($child_dirs as $child_dir){
			if($child_dir != '.' && $child_dir != '..'){
				if(is_dir($dir."/".$child_dir)){$files=array_merge($files,scandirAll($dir."/".$child_dir));}
				else{array_push($files,$dir."/".$child_dir);}
			}
		}
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

	$ACT=$_POST["a"];
	$PWD=$_POST["pwd"];
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
						if(!$fileisdir){$filesize=filesize(".".$_POST["name"].$filename)/1024;}else{$filesize=0;}
						array_push($fileArrayModified,array(
							"name"=>$filename,
							"dir"=>$fileisdir,
							"size"=>$filesize
						));
					}
					echo "200||".rawurlencode(json_encode($fileArrayModified));
				}else{
					echo "1001";
				}
			}elseif($ACT=="getfile"){
				echo file_get_contents(".".$_POST["name"]);
			}elseif($ACT=="save"){
				file_put_contents(".".$_POST["name"],$_POST["data"]);
				echo "200";
			}elseif($ACT=="zip"){
				$zipResult=create_zip(scandirAll(realpath(".".$_POST["name"]),true),"./FileAdmin_".time().".zip",false);
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
			    $latest=file_get_contents("https://raw.githubusercontent.com/YanJi314/FileAdmin/main/api/latest?stamp=".time());
			    if($latest && $latest!=$VERSION){
			        $updinfo=file_get_contents("https://raw.githubusercontent.com/YanJi314/FileAdmin/main/api/updinfo?stamp=".time());
                    if($updinfo){
                        echo $updinfo;
                    }else{echo "1002";}
			    }else{echo "1001";}
			}elseif($ACT=="applyversion"){
			    $updater=file_get_contents("https://raw.githubusercontent.com/YanJi314/FileAdmin/main/api/updater?stamp=".time());
			    if($updater){
			        file_put_contents("./FileAdminUpdater.php",$updater);
			        header("location: ./FileAdminUpdater.php?famain=".end(explode("/",$_SERVER['PHP_SELF'])));
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
	}else{
?>

<!--
	SimSoft FileAdmin 前端部分
	由盐鸡开发的一款轻量级文件管理器
	© 2022 SimSoft
-->


<!DOCTYPE html>
<html onmousedown="hideContextMenu()" oncontextmenu="showContextMenu()" onclick="fileSelected=[];loadFileSelected();">
	<head>
	    <title>FileAdmin</title>
		<meta name="viewport" content="width=device-width">
		<link rel="icon" href="//asset.simsoft.top/fileadmin.png">
	</head>
	<style>
		*{box-sizing:border-box;}
		body{margin:0;user-select:none;margin-top:45px;font-family:微软雅黑;background:#f5f5f5;min-height:100%;}
		::-webkit-scrollbar{display:none;}
		.title{position:fixed;top:0;left:0;right:0;height:fit-content;box-shadow:0 0 5px 0 rgba(0,0,0,.4);height:40px;background:white;z-index:5;}
		.appName{font-size:1.5em;position:absolute;top:0;height:fit-content;bottom:0;left:10px;margin:auto}
		.appName b{color:#1e9fff;}
		.title svg{position:absolute;top:0;bottom:0;right:10px;margin:auto;transform:rotate(180deg)}
		.module{display:none;background:white;}
		.module.shown{display:block;}
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
		.files{margin:10px;background:transparent;text-align:center;}
		#fileList{margin-top:5px;border-radius:5px;background:white;overflow:hidden;margin-bottom:10px;display:inline-block;text-align:left;max-width:500px;width:100%}
		#fileList center{padding:30px 0;opacity:.6}
		#fileList .file{border-top:1px solid #f5f5f5;padding:10px;text-align:center;}
		#fileList .file:first-child{border-top:none;}
		#fileList .file:hover{background:rgba(0,0,0,.09);}
		#fileList .file:active{background:rgba(0,0,0,.12)}
		#fileList .file .fileName::before{display:inline-block;margin-right:5px;width:25px;}
		#fileList .file[data-isdir^=false] .fileName::before{content:"📄"}
		#fileList .file[data-isdir^=true] .fileName::before{content:"📂"}
		#fileList .file .fileName{display:inline-block;width:calc(100% - 100px);text-align:left;vertical-align:middle;font-size:1.1em;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
		#fileList .file .size{display:inline-block;width:90px;text-align:right;vertical-align:middle;opacity:.5;}
		#fileList .file[data-isdir^=true] .size{opacity:0;}
		#fileList .file.selected{background:#1e9fff;color:white;}
		.texteditor{margin:10px;}
		#textEditor{border-radius:5px;position:absolute;top:50px;left:10px;right:10px;height:calc(100% - 60px);border:1px solid rgba(0,0,0,.1);overflow:hidden;}
		#textEditor *::-webkit-scrollbar{display:block;width:10px;height:10px;background:#ebebeb;}
		#textEditor *::-webkit-scrollbar-thumb{border-radius:5px;background:#dcdcdc;}
		contextmenu{z-index:30;position:fixed;border:1px solid #c1c1c1;width:100px;height:fit-content;background:white;overflow:hidden;box-shadow:1px 1px 2px 0 rgba(0,0,0,.2);}
		contextmenu button{outline:none;display:block;border:0;padding:5px 10px;background:white;width:100%;text-align:left;}
		contextmenu button:hover{background:rgba(0,0,0,.05);}
		contextmenu button:active{background:rgba(0,0,0,.1);}
		.imgviewer{background:transparent;}
		#imgviewer{width:calc(100% - 10px);height:calc(100vh - 100px);background:white;margin:5px;border:1px solid rgba(0,0,0,.1);border-radius:5px;object-fit:contain;}
		.updinfo{margin:10px;padding:10px;}
		#updinfo{padding:10px;}
		.upload{inset:0;margin:auto;height:fit-content;width:340px;padding:10px;border-radius:5px;position:fixed;overflow:hidden;}
		.uploadProgress{height:8px;border-radius:4px;background:#f0f0f0;overflow:hidden;margin:10px 0;}
		#uploadProgressBar{height:8px;transition:width .2s;background:#1e9fff;width:0;}
		.uploadText{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;opacity:.7}
		@media screen and (min-width:600px) {
			.menu{top:-30px;transition:top .2s;position:fixed;z-index:20;right:40px;left:150px;height:24px;text-align:right;}
			.menu button{outline:none;border:0;background:#f5f5f5;height:100%;width:45px;border-radius:5px;}
			.menu button.big{width:70px}
			.menu button:hover{background:#f9f9f9}
			.menu button:active{background:#f0f0f0}
			.menu.shown{top:8px;}
			.loading{position:fixed;top:0;left:140px;bottom:calc(100% - 40px);margin:auto;z-index:20;height:fit-content;opacity:.5;font-size:.9em;}
		}
		@media screen and (max-width:600px) {
			body{margin-bottom:50px;}
			.menu{bottom:-35px;transition:bottom .2s;box-shadow:0 0 5px 0 rgba(0,0,0,.4);background:white;position:fixed;z-index:10;right:0;left:0;height:30px;text-align:center;overflow-y:scroll;white-space:nowrap}
			.menu button{outline:none;border:0;height:100%;width:fit-content;background:transparent;width:30px;padding:0;}
			.menu button.big{width:60px}
			.menu.shown{bottom:0;}
			#textEditor{height:calc(100% - 90px)}
			.loading{position:fixed;top:0;right:50px;bottom:calc(100% - 40px);margin:auto;z-index:20;height:fit-content;opacity:.5;font-size:.9em;}
		}
	</style>
	<body>
		<div class="title">
			<div class="appName" onclick="chkupd()">File<b>Admin</b></div>
			<svg id="logoutBtn" onclick="logout()" width="20" height="20" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="48" height="48" fill="white" fill-opacity="0.01"/><path d="M23.9917 6L6 6L6 42H24" stroke="#000000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M33 33L42 24L33 15" stroke="#000000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 23.9917H42" stroke="#000000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</div>
		<div class="module loading shown" data-module="loading">正在请求...</div>

		<!--登录页-->
		<div class="module login" data-module="login">
			<div class="loginTitle">登录 FileAdmin</div>
			<input autofocus id="loginPassword" placeholder="请输入密码 (/▽＼)" type="password" onkeydown="loginCheckEnter(event)"><button onclick="login()" class="loginBtn">→</button>
		</div>
		
		<!--文件列表页-->
		<div class="module files" data-module="files">
			<div class="addressBar"><button title="根目录" onclick="dirOperating='/';loadFileList('/')">/</button><button title="上级目录" onclick="previousDir()"><</button><div id="addressBar" onclick="editAddressBar()">/</div></div>
			<br><div id="fileList" onclick="event.stopPropagation();"></div>
		</div>
		<div class="menu" data-menu="files-noselect" onclick="event.stopPropagation();">
			<button onclick="fileSelected=fileListOperating;loadFileSelected();">全选</button>
			<button onclick="loadFileList(dirOperating)">刷新</button>
			<button onclick="showMenu('files-upload')">上传</button>
			<button onclick="newDir()" class="big">新建目录</button>
			<button onclick="newFile()" class="big">新建文件</button>
			<button onclick="zipCurrentDir()">打包</button>
		</div>
		<div class="menu" data-menu="files-singleselect" onclick="event.stopPropagation();">
			<button onclick="fileSelected=fileListOperating;loadFileSelected();">全选</button>
			<button onclick="fileSelected=[];loadFileSelected();" class="big">取消选中</button>
			<button onclick="renameFile();">改名</button>
			<button onclick="downCurrFile();">下载</button>
			<button onclick="delFile();">删除</button>
		</div>
		<div class="menu" data-menu="files-multiselect" onclick="event.stopPropagation();">
			<button onclick="fileSelected=fileListOperating;loadFileSelected();">全选</button>
			<button onclick="fileSelected=[];loadFileSelected();" class="big">取消选中</button>
			<button onclick="delFile();">删除</button>
		</div>
		<div class="menu" data-menu="files-upload">
			<button class="big" onclick="document.getElementById('filesUploadInput').click()">上传文件</button>
			<button class="big" onclick="document.getElementById('folderUploadInput').click()">上传目录</button>
			<button onclick="loadFileMenu();">取消</button>
		</div>
		
		<!--文件上传器-->
		<div class="module upload" data-module="upload">
		    <div style="font-size:1.5em;text-align:center;">正在上传 ψ(._. )></div>
		    <div class="uploadProgress"><div id="uploadProgressBar"></div></div>
		    <div class="uploadText">当前上传：<span id="uploadText-CurrFile"></span></div>
		    <div class="uploadText">当前进度：<span id="uploadText-CurrProg"></span></div>
		    <div class="uploadText">目标目录：根目录<span id="uploadText-DestDir"></span></div>
		    <div class="uploadText">等待上传：<span id="uploadText-Waiting"></span> 个文件</div>
		</div>
		
		<!--纯文本编辑器-->
		<div class="module texteditor" data-module="texteditor">
			<div id="textEditor"></div>
		</div>
		<div class="menu" data-menu="texteditor">
			<button onclick="saveFile()" id="saveBtn">保存</button>
			<button onclick="viewFile(fileEditing,true)">刷新</button>
			<button onclick="setWrap(this)">换行</button>
			<button onclick="window.open('.'+dirOperating+fileEditing)">预览</button>
			<button onclick="loadFileList(dirOperating)">返回</button>
		</div>
		
		<!--图片预览器-->
		<div class="module imgviewer" data-module="imgviewer"><img id="imgviewer"></div>
		<div class="menu" data-menu="imgviewer">
			<button onclick="location=imageViewingUrl" class="big">下载图片</button>
			<button onclick="document.getElementById('imgviewer').src='';loadFileList(dirOperating)">返回</button>
		</div>
		
			
		<!--更新信息-->
		<div class="module updinfo" data-module="updinfo">
		    <div style="font-size:1.5em;border-bottom:1px solid #f5f5f5;text-align:center;padding:10px;">检测到更新</div>
		    <div id="updinfo"></div>
		</div>
		<div class="menu" data-menu="updinfo">
			<button onclick="applupd()" class="big">应用更新</button>
			<button onclick="dirOperating='/';loadFileList('/');">取消</button>
		</div>
		
		<div style="display:none">
		    <input type="file" multiple webkitdirectory id="folderUploadInput" onchange="addDirToUploads(this)">
		    <input type="file" multiple id="filesUploadInput" onchange="addFilesToUploads(this)">
        </div>
	</body>
	
	<script>
//=========================================初始化
		window.onload=function(){
			dirOperating="/";
			request("check",null,function(){loadFileList(dirOperating)});
			if(navigator.userAgent.indexOf("Chrome")==-1){alert("FileAdmin 目前仅兼容 Google Chrome 和 Microsoft Edge 的最新版本，使用其他浏览器访问可能导致未知错误。")}
		}
		window.onkeydown=function(){
			if(event.keyCode==191){
				if(document.querySelector(".files.shown")){editAddressBar();}
				if(document.querySelector(".login.shown")){event.preventDefault();document.getElementById("loginPassword").focus();}
			}else if(event.ctrlKey==true&&event.keyCode==83){
				event.preventDefault();
				if(document.querySelector(".texteditor.shown")){saveFile();}
			}else if(event.keyCode==27){
				if(document.querySelector(".texteditor.shown")){loadFileList(dirOperating);}
				else if(document.querySelector(".files.shown")){previousDir();}
			}else if(event.ctrlKey==true&&event.keyCode==65){
				if(document.querySelector(".files.shown")){event.preventDefault();fileSelected=fileListOperating;loadFileSelected();}
			}
		}
//=========================================公共函数
		function request(act,txt,callback){
			if(txt){fetchBody="a="+act+"&pwd="+encodeURIComponent(localStorage.getItem("FileAdmin_Password"))+"&"+txt;}
			else{fetchBody="a="+act+"&pwd="+encodeURIComponent(localStorage.getItem("FileAdmin_Password"));}
			fetch('',{
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
		    document.title="FileAdmin | 轻量级文件管理";
			hideMenu();
			if(document.querySelector(".module.shown")){document.querySelector(".module.shown").classList.remove("shown");}
			document.querySelector(".module[data-module^='"+name+"']").classList.add("shown");
			if(name=="login"){document.getElementById("logoutBtn").style.display="none";}else{document.getElementById("logoutBtn").style.display="block";}
		}
		function showMenu(name){
			if(document.querySelector(".menu.shown")){document.querySelector(".menu.shown").classList.remove("shown");}
			document.querySelector(".menu[data-menu^='"+name+"']").classList.add("shown");
		}
		function hideMenu(){
			if(document.querySelector(".menu.shown")){document.querySelector(".menu.shown").classList.remove("shown");}
		}
//=========================================登录
		function loginCheckEnter(eve){if(eve.keyCode==13){login()}}
		function login(){
			showModule("loading");
			request("login","loginPwd="+document.getElementById("loginPassword").value,function(code,msg){
				if(code==200){
					localStorage.setItem("FileAdmin_Password",msg);
					loadFileList(dirOperating);
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
        }
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
        }
        function addDirToUploadArr(file){
		    let relativeDir=file.webkitRelativePath.split("/").slice(0,file.webkitRelativePath.split("/").length-2);
            waitingToUpload.push({"file":file,"dir":dirOperating+relativeDir});
            waitingToUploadCount++;
        }
        function uploadFileFromList(id){
            if(!waitingToUpload[id]){loadFileList(dirOperating)}else{
                waitingToUploadCount--;
        		document.getElementById("uploadText-CurrFile").innerText=waitingToUpload[id]["file"]["name"];
        		document.getElementById("uploadText-Waiting").innerText=waitingToUploadCount;
        		document.getElementById("uploadText-DestDir").innerText=waitingToUpload[id]["dir"];
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
        			document.getElementById("uploadText-CurrProg").innerText=percent+" ( "+Math.round(eve.loaded/1024)+"KB / "+Math.round(eve.total/1024)+"KB )";
        		}
        		xhr.send(fd);
            }
        }
//========================================文件管理器
		function loadFileList(dir){
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
		}
		function addToFileListHtml(data){
			if(data.name!="."&&data.name!=".."){
			    fileListOperating.push(data.name);
				fileListHtml=fileListHtml+`<div class="file" data-isdir=`+data.dir+` data-filename="`+data.name+`" onclick="viewFile(this)" oncontextmenu="fileContextMenu(this)">
					<div class="fileName">`+data.name+`</div>
					<div class="size">`+Math.round(data.size*100)/100+`KB</div>
				</div>`;
			}
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
		function viewFile(ele,byname){
			if(!byname){
				fileIsDir=ele.getAttribute("data-isdir");
				fileName=ele.getAttribute("data-filename");
			}else{fileIsDir=false;fileName=ele;}
			if(fileSelected.length==0){
    			fileType=fileName.split(".")[fileName.split(".").length-1].toLowerCase();
    			fileEditing=fileName;
    			if(fileIsDir=="true"){
    				dirOperating=dirOperating+fileName+"/";
    				loadFileList(dirOperating);
    			}else{
    				textMode=null;
    				if(fileType=="html"||fileType=="htm"){textMode="html";}
    				else if(fileType=="php"){textMode="php";}
    				else if(fileType=="json"){textMode="json";}
    				else if(fileType=="js"){textMode="javascript";}
    				else if(fileType=="css"){textMode="css";}
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
    							loadFileList(dirOperating);
    						})
    					}
    				}}
    				else if(fileType=="rar"||fileType=="7z"){alert("不支持此类文件解压，请使用.zip格式 (っ´Ι`)っ");}
    				else if(fileType=="jpg"||fileType=="png"||fileType=="jpeg"||fileType=="gif"||fileType=="webp"||fileType=="ico"){
    				    showModule("imgviewer");
    				    showMenu("imgviewer");
    				    imageViewingUrl="?a=down&pwd="+encodeURIComponent(localStorage.getItem("FileAdmin_Password"))+"&name="+encodeURI(dirOperating+fileName);
    				    document.getElementById("imgviewer").src=imageViewingUrl;
    				}
    				else{if(confirm("此文件的格式目前不被支持捏..\n您是否希望尝试使用文本编辑器打开 (⊙_⊙)？")){textMode="html"}}
    				if(textMode){
    					showModule("loading");
    					request("getfile","name="+dirOperating+fileName,function(c,d,file){
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
    						document.title=fileName+" | FileAdmin"
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
		}
		function previousDir(){
			if(dirOperating=="/"){alert("您已经在根目录啦 ㄟ( ▔, ▔ )ㄏ");}else{
				let dirArr=dirOperating.split("/").slice(0,dirOperating.split("/").length-2);
				dirName="";
				dirArr.forEach(arrToDir);
				dirOperating=dirName;
				loadFileList(dirOperating);
			}
		}
		function arrToDir(item){
			dirName+=item+"/"
		}
		function loadFileMenu(){
		    if(document.querySelector(".files.shown")){
    		    if(fileSelected.length==0){showMenu("files-noselect")}
    		    else if(fileSelected.length==1){showMenu("files-singleselect")}
    		    else{showMenu("files-multiselect")}
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
					loadFileList(dirOperating);
				})
			}
		}
		function newFile(){
			let filename=prompt("📄 请输入新文件名称 (●'◡'●)");
			if(filename){
				showModule("loading")
				if(filename.indexOf("/")==-1){
					request("save","name="+encodeURIComponent(dirOperating+filename),function(){loadFileList(dirOperating)});
				}else{alert("文件名不能包含特殊字符呐 (；′⌒`)");}
			}
		}
		function newDir(){
			let filename=prompt("📂 请输入新目录名称 (●'◡'●)");
			if(filename){
				showModule("loading")
				if(filename.indexOf("/")==-1){
					request("mkdir","name="+encodeURIComponent(dirOperating+filename),function(){loadFileList(dirOperating)});
				}else{alert("目录名不能包含特殊字符呐 (；′⌒`)");}
			}
		}
//========================================单选中操作
        function renameFile(){
            let newName=prompt("请输入文件的新名称(*^▽^*)",fileSelected[0]);
            if(newName){
                if(newName.indexOf("/")==-1&&newName.indexOf("&")==-1){
                    showModule("loading");
                    request("rename","dir="+encodeURIComponent(dirOperating)+"&old="+encodeURIComponent(fileSelected[0])+"&new="+encodeURIComponent(newName),function(c){
                        if(c==1002){alert("文件 “"+newName+"” 已经存在啦 (；′⌒`)")}else if(c!=200){alert("出现未知错误 (；′⌒`)")}
                        loadFileList(dirOperating)
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
                request("del","files="+encodeURIComponent(fileDelStr)+"&dir="+dirOperating,function(){loadFileList(dirOperating)});
            }
        }
//========================================文本编辑器
		function saveFile(){
			document.getElementById("saveBtn").innerText="······";
			request("save","name="+dirOperating+fileEditing+"&data="+encodeURIComponent(textEditor.getValue()) ,function(code){
				if(code==200){
					document.getElementById("saveBtn").innerText="完成";
					setTimeout(function(){document.getElementById("saveBtn").innerText="保存";},700)
				}else{
					alert("出现未知错误（＞人＜；）");
					document.getElementById("saveBtn").innerText="保存";
				}
			})
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
                    if(event.clientX>document.getElementsByTagName("html")[0].clientWidth-100){menuElem.style.left=event.clientX-100+"px";}
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
                fileSelected=[ele.getAttribute("data-filename")];
                loadFileSelected();
                showContextMenu();
            }else{
                showContextMenu();
            }
        }
//========================================检查更新
        function chkupd(){
            showModule("loading")
            request("chkupd",null,function(c,d,o){
                if(o=="1001"){dirOperating="/";loadFileList("/");alert("您的FileAdmin已是最新版啦~");}
                else if(o=="1002"){dirOperating="/";loadFileList("/");alert("获取更新失败，您的服务器网络环境可能无法访问GitHub (；′⌒`)");}
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
                else{alert("更新失败惹..");showModule("updinfo");showMenu("updinfo")}
            })
        }
//========================================退出登录
		function logout(){
			if(confirm("您真的要退出登录嘛？＞﹏＜")){
				localStorage.setItem("FileAdmin_Password",0);
				showModule("login");
			}
		}
	</script>
	<script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/ace.min.js"></script>
	<script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/mode-javascript.min.js"></script>
	<script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/mode-html.min.js"></script>
	<script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/mode-php.min.js"></script>
	<script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/mode-css.js"></script>
	<script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/mode-json.min.js"></script>
	<script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/theme-chrome.js"></script>
	<script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/ext-language_tools.min.js"></script>
</html>


<?php
	}
?>
