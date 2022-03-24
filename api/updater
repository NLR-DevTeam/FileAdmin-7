<?php
    error_reporting(0);
    $famain=$_GET["famain"];
    $famainphp=file_get_contents($famain);
    $currpwd=explode('<?php $PASSWORD="',$famainphp)[1];
    $currpwd=explode('"; $VERSION=',$currpwd)[0];
    $newver=file_get_contents("https://fileadmin.vercel.app/fileadmin.php");
    if($newver){
        $rplcode=str_replace("TYPE-YOUR-PASSWORD-HERE",$currpwd,$newver);
        file_put_contents($famain,$rplcode);
        echo "200";
    }else{
        echo "1001";
    }
    unlink("FileAdminUpdater.php");
