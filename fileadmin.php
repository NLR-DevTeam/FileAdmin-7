<?php
    $PASSWORD="FileAdmin";
    $VERSION=5.1;
?><?php
    error_reporting(0);
    
//=============================一些找来的轮子
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
                    echo "200||".urlencode(json_encode($fileArrayModified));
                }else{
                    echo "1001";
                }
            }elseif($ACT=="getfile"){
                echo file_get_contents(".".$_POST["name"]);
            }elseif($ACT=="save"){
                if(realpath("./".$_SERVER['PHP_SELF'])==realpath(".".$_POST["name"])){
                    echo "1001";
                }else{
                    file_put_contents(".".$_POST["name"],$_POST["data"]);
                    echo "200";
                }
            }elseif($ACT=="zip"){
        		$zipResult=create_zip(scandirAll(realpath(".".$_POST["name"]),true),"./FileAdmin_".time().".zip",false);
        		if($zipResult){echo "200";}else{echo "1001";}
            }elseif($ACT=="mkdir"){
        		mkdir(".".$_POST["name"]);
        		echo "200";
            }
        }else{echo "1000";}
    }else{
?>

<!--
SimSoft FileAdmin
A light php file manager written by YanJi.
© 2022 SimSoft
-->


<!DOCTYPE html>
<html>
    <head>
        <title>FileAdmin | 轻量级文件管理</title>
	    <meta name="viewport" content="width=device-width">
	    <link rel="icon" href="//asset.simsoft.top/fileadmin.png">
    </head>
    <style>
        *{box-sizing:border-box;}
        body{margin:0;user-select:none;margin-top:45px;font-family:微软雅黑;background:#f5f5f5;}
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
        .addressBar{margin-top:50px;border-radius:5px;background:white;overflow:hidden;}
        .addressBar button{font-weight:bold;width:30px;height:32px;border:0;outline:0;background:transparent;border-right:1px solid #f5f5f5;vertical-align:middle;}
        .addressBar button:hover{background:rgba(0,0,0,.09);}
        .addressBar button:active{background:rgba(0,0,0,.12);}
        .addressBar div{vertical-align:middle;display:inline-block;width:calc(100% - 60px);padding:0 10px;overflow-x:scroll;white-space:nowrap}
        .files{margin:10px;background:transparent;}
        #fileList{margin-top:5px;border-radius:5px;background:white;overflow:hidden;margin-bottom:10px;}
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
        .texteditor{margin:10px;}
        #textEditor{border-radius:5px;position:absolute;top:50px;left:10px;right:10px;height:calc(100% - 60px);border:1px solid rgba(0,0,0,.1);overflow:hidden;}
        #textEditor *::-webkit-scrollbar{display:block;width:10px;height:10px;background:#ebebeb;}
        #textEditor *::-webkit-scrollbar-thumb{border-radius:5px;background:#dcdcdc;}
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
            <div class="appName">File<b>Admin</b></div>
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
            <div id="fileList"></div>
        </div>
        <div class="menu" data-menu="files-noselect">
            <button onclick="loadFileList(dirOperating)">刷新</button>
            <button onclick="newDir()" class="big">新建目录</button>
            <button onclick="newFile()" class="big">新建文件</button>
            <button onclick="zipCurrentDir()">打包</button>
        </div>
        
        <!--纯文本编辑器-->
        <div class="module texteditor" data-module="texteditor">
            <div id="textEditor"></div>
        </div>
        <div class="menu" data-menu="texteditor">
            <button onclick="saveFile()" id="saveBtn">保存</button>
            <button onclick="viewFile(fileEditing,true)">刷新</button>
            <button onclick="setWrap(this)">换行</button>
            <button onclick="loadFileList(dirOperating)">返回</button>
        </div>
        
    </body>
    
    <script>
//=========================================初始化
        window.onload=function(){
            dirOperating="/";
            request("check",null,function(){loadFileList(dirOperating)});
        }
        window.onkeydown=function(){
            if(event.keyCode==191){
                if(document.querySelector(".files.shown")){editAddressBar();}
                if(document.querySelector(".login.shown")){event.preventDefault();document.getElementById("loginPassword").focus();}
            }else if(event.ctrlKey==true&&event.keyCode==83){
                event.preventDefault();
                if(document.querySelector(".texteditor.shown")){saveFile();}
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
            .catch(err=>{alert(err)})
        }
        function showModule(name){
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
//========================================文件管理器
        function loadFileList(dir){
            document.getElementById("addressBar").innerText="根目录"+dir.replaceAll("/"," / ");
            showModule("loading");
            request("files","name="+dir,function(code,data){
                if(code==200){
                    fileListArr=JSON.parse(decodeURIComponent(data));
                    fileListHtml="";
                    fileListArr.forEach(addToFileListHtml);
                    document.getElementById("fileList").innerHTML=fileListHtml;
                    if(fileListHtml==""){
                        document.getElementById("fileList").innerHTML="<center>请求的目录为空 ヽ(*。>Д<)o゜</center>"
                    }
                }else if(code=="1001"){document.getElementById("fileList").innerHTML="<center>请求的目录不存在捏 (ノへ￣、)</center>"}
                else if(code="1002"){document.getElementById("fileList").innerHTML="<center>目录名称格式有误 (ﾟДﾟ*)ﾉ</center>"}
                showModule("files");
                showMenu("files-noselect")
            })
        }
        function addToFileListHtml(data){
            if(data.name!="."&&data.name!=".."){
                fileListHtml=fileListHtml+`<div class="file" data-isdir=`+data.dir+` data-filename="`+data.name+`" onclick="viewFile(this)">
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
            }else{
                fileIsDir=false;
                fileName=ele;
            }
            fileType=fileName.split(".")[fileName.split(".").length-1];
            fileEditing=fileName;
            if(fileIsDir=="true"){
                dirOperating=dirOperating+fileName+"/";
                loadFileList(dirOperating);
            }else{
                showModule("loading");
                textMode=null;
                if(fileType=="html"||fileType=="htm"){textMode="html";}
                else if(fileType=="php"){textMode="php";}
                else if(fileType=="json"){textMode="json";}
                else if(fileType=="js"){textMode="javascript";}
                else if(fileType=="css"){textMode="css";}
                if(textMode){
                    request("getfile","name="+dirOperating+fileName,function(c,d,file){
                        ace.config.set('basePath', 'https://lf6-cdn-tos.bytecdntp.com/cdn/expire-100-y/ace/1.4.14/')
                        textEditor=ace.edit("textEditor");
                        textEditor.setOption("enableLiveAutocompletion",true);
                        textEditor.session.setValue(file);
                        textEditor.setTheme("ace/theme/chrome");
                        textEditor.gotoLine(1);
                        textEditor.setShowPrintMargin(false);
                        textEditor.session.setMode("ace/mode/"+textMode);
                        showModule("texteditor");
                        showMenu("texteditor");
                    });
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
//========================================单文件操作
        function zipCurrentDir(){
            if(confirm("您确实想将当前目录打包为Zip文件嘛 (⊙_⊙)？\nTip: 部分环境可能不支持此功能")){
                showModule("loading")
                request("zip","name="+dirOperating,function(code){
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
                    request("save","name="+dirOperating+filename,function(){loadFileList(dirOperating)});
                }else{alert("文件名不能包含特殊字符呐 (；′⌒`)");}
            }
        }
        function newDir(){
            let filename=prompt("📂 请输入新目录名称 (●'◡'●)");
            if(filename){
                showModule("loading")
                if(filename.indexOf("/")==-1){
                    request("mkdir","name="+dirOperating+filename,function(){loadFileList(dirOperating)});
                }else{alert("目录名不能包含特殊字符呐 (；′⌒`)");}
            }
        }
//========================================文本编辑器
        function saveFile(){
            document.getElementById("saveBtn").innerText="······";
            request("save","name="+dirOperating+fileEditing+"&data="+encodeURIComponent(textEditor.getValue()) ,function(code){
                if(code==200){
                    document.getElementById("saveBtn").innerText="完成";
                    setTimeout(function(){document.getElementById("saveBtn").innerText="保存";},700)
                }else if(code==1001){
                    alert("由于安全原因，FileAdmin 无法修改本体文件。如需修改请自行使用主机控制面板修改~ ");
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
