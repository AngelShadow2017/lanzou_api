<html>
    <head>
        <meta charset="utf-8"/>
        <title>蓝奏 api</title>
    </head>
<?php
    /**
     * @package Lanzou
     * @Origin_author Mlooc
     * @Origin_version 1.0.0
     * @link http://api.liusy.tk
     * @Modify_author Zero_One
     * @Modify_version 1.0.0_mod
     * @Zero_One_Link http://angelshadow.cn
     */
function MloocCurl($url,$method,$ifurl,$post_data){
    $UserAgent = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36';#设置ua
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    if ($method == "post") {
        curl_setopt($curl, CURLOPT_REFERER, $ifurl); 
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    }
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
if (!empty($_GET['url'])) {
    $url = $_GET['url'];
    #第一步
    if(empty($_GET["pw"])){
        $ruleMatchDetailInList = "~ifr2\"\sname=\"[\s\S]*?\"\ssrc=\"\/(.*?)\"~";
        preg_match_all($ruleMatchDetailInList, MloocCurl($url,null,null,null),$link);
        $index = 0;
        for($i=0;$i<count($link[1]);$i++){
            if($link[1][$i]!="fn?v2"){
                $index = $i;
                break;
            }
        }
        $ifurl = "https://www.lanzous.com/".$link[1][$index];
        //print_r($ifurl);
        #第二步
        $ruleMatchDetailInList = "~var ajaxup = '([^\]]*)';//~";
        preg_match($ruleMatchDetailInList, MloocCurl($ifurl,null,null,null),$segment);
        #第三步
        #post提交的数据
        $post_data = array(
            "action" => "downprocess",
            "sign" => $segment[1],
            "ves" => 1,
            "p" => $_GET["pw"]
        );
    }else{
        $ifurl = $url;
        #有密码第一步跳过
        $ruleMatchDetailInList = "~data \: 'action=downprocess&sign=([^\]]*)&p='~";
        $ulc = MloocCurl($url,null,null,null);
        preg_match($ruleMatchDetailInList, $ulc,$segment);
        //print_r($segment[1]);
        #第三步
        #post提交的数据
        $post_data = array(
            "action" => "downprocess",
            "sign" => $segment[1],
            "p" => $_GET["pw"]
        );
    }
    
    $obj = json_decode(MloocCurl("https://www.lanzous.com/ajaxm.php","post",$ifurl,$post_data));#json解析
    if ($obj->dom == "") {#判断链接是否正确
        echo "链接有误！";
    }else{
        $downUrl = $obj->dom."/file/".$obj->url;
        if (!empty($_GET['type'])) {
            $type = $_GET['type'];
            if ($type == "down") {
                header('Location:'.$downUrl);#直接下载
            }else{
                echo $obj->dom."/file/".$obj->url;#输出直链
            }
        }else{
            echo $obj->dom."/file/".$obj->url;#输出直链
        }
    }
}else{
    $result_url = str_replace("index.php","","//".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?url=https://www.lanzous.com/igVmkdrpalg");
    $result_urlpw = str_replace("index.php","","//".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?url=https://zeroone.lanzous.com/i0M4ndrpasd");
    echo "示例：";
    echo "<br/>";
    echo "直接下载："."<a href='".$result_url."&type=down' target='_blank'>".$result_url."&type=down</a>";
    echo "<br/>";
    echo "输出直链："."<a href='".$result_url."' target='_blank'>".$result_url."</a>";
    echo "<br/>";
    echo "带密码直接下载："."<a href='".$result_urlpw."&type=down&pw=f4eo' target='_blank'>".$result_urlpw."&type=down&pw=f4eo</a>";
    echo "<br/>";
    echo "带密码直链："."<a href='".$result_urlpw."&pw=f4eo' target='_blank'>".$result_urlpw."&pw=f4eo</a>";
    echo "<br/>";
    echo "参数：<br/>type=down 直接下载不是直链<br/>pw=xxxx 密码";
}
    ?>
</html>