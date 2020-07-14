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
function cutStr($begin,$end,$str){
    $b = mb_strpos($str,$begin) + mb_strlen($begin);
    $e = mb_strpos($str,$end) - $b;
    return mb_substr($str,$b,$e);
}
//即使错误（不是json字符串）也获取json的值的函数（单层）
function getWrongJsonValue($json,$key){
    $json = join("",explode(" ",$json));
    //echo $json;
    $ruleMatchDetailInList = "/\"".$key."\":[\s\S]*?,/i";
    preg_match($ruleMatchDetailInList,$json,$val);
    return cutStr("\"".$key."\":",",",$val[0]);
}
if (!empty($_GET['url'])) {
    $url = $_GET['url'];
    if($_GET["dir"]!="true"){
        //获取文件
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
        //共有操作
        //$ruleMatchDetailInList = "~data \: \{([^\]]*)\},\n~";
        $pg = 1;//第一页
        $ruleMatchDetailInList = "/data \: \{[\s\S]*?\},/i";
        $tmpHref = MloocCurl($url,null,null,null);//缓存爬下来网页的内容
        preg_match($ruleMatchDetailInList, $tmpHref,$link);
        $ruleMatchDetailInList = "/\{[\s\S]*?\}/i";
        preg_match($ruleMatchDetailInList, $link[0],$link);//获取指定的json
        $json = join("\"",explode("'",$link[0]));
        $key1=getWrongJsonValue($json,"t");
        $key2=getWrongJsonValue($json,"k");
        $ruleMatchDetailInList = "/var ".$key1." = '([\s\S]*?)';/i";
        preg_match($ruleMatchDetailInList, $tmpHref,$key1);
        $key1 = $key1[1];
        $ruleMatchDetailInList = "/var ".$key2." = '([\s\S]*?)';/i";
        preg_match($ruleMatchDetailInList, $tmpHref,$key2);
        $key2 = $key2[1];//获取两个key值
        $fid = getWrongJsonValue($json,"fid");
        $uid = getWrongJsonValue($json,"uid");//获取fid和uid
        $post_data = array(
            "lx" => 2,
            "fid" => $fid,
            "uid" => $uid,
            "rep" => "0",
            't' => $key1,
            'k' => $key2,
            'up' => 1,
            'pg' => $pg,
        );
        if(!empty($_GET["pw"])){
            $post_data["ls"] = 1;
            $post_data["pwd"] = $_GET["pw"];
        }
        $totalFileArr = array();
        do{
            $obj = json_decode(MloocCurl("https://www.lanzous.com/filemoreajax.php","post",$url,$post_data));
            $post_data["pg"] = ++$pg;
            if($obj->zt==1){
                //print_r($obj->text);
                $totalFileArr = array_merge($totalFileArr,$obj->text);
            }else if($obj->zt==4){
                $post_data["pg"] = --$pg;
            }
        }while($obj->zt==1||$obj->zt==4);
        //print_r($totalFileArr);
        switch($_GET["type"]){
            case "json":
                echo "{data:".json_encode($totalFileArr)."}";
            break;
            case "jsonp":
                echo $_GET["jsonpname"]."({data:".json_encode($totalFileArr)."})";
            break;
            case "olist":
                /*  [icon] =&gt; txt
            [t] =&gt; 0
            [id] =&gt; iMy5de3b7rc
            [name_all] =&gt; a - 副本 (22) - 副本 - 副本.txt
            [size] =&gt; 1.0 B
            [time] =&gt; 11 小时前
            [duan] =&gt; ie3b7r
            [p_ico] =&gt; 0*/
                //print_r($totalFileArr);
                for($i=0;$i<count($totalFileArr);$i++){
                    echo "<a href='//www.lanzous.com/".$totalFileArr[$i]->id."'>".$totalFileArr[$i]->name_all."</a><br/>";
                }
            break;
            case "list":
                for($i=0;$i<count($totalFileArr);$i++){
                    echo "<a href='?url=https://www.lanzous.com/".$totalFileArr[$i]->id."&type=down'>".$totalFileArr[$i]->name_all."</a><br/>";
                }
            break;
            case "ulist":
                for($i=0;$i<count($totalFileArr);$i++){
                    echo "<a href='?url=https://www.lanzous.com/".$totalFileArr[$i]->id."'>".$totalFileArr[$i]->name_all."</a><br/>";
                }
            break;
            case "down":
                for($i=0;$i<count($totalFileArr);$i++){
                    echo "<script>window.open('"."?url=https://www.lanzous.com/".$totalFileArr[$i]->id."&type=down"."');</script>";
                }
            break;
            case "count":
                echo count($totalFileArr);
            break;
            default:
                for($i=0;$i<count($totalFileArr);$i++){
                    echo "<a href='?url=https://www.lanzous.com/".$totalFileArr[$i]->id."&type=down'>".$totalFileArr[$i]->name_all."</a><br/>";
                }
        }
    }
}else if($_GET["show"]!="0"){
    $result_url = str_replace("index.php","","//".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?url=https://www.lanzous.com/igVmkdrpalg");
    $result_urlpw = str_replace("index.php","","//".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?url=https://zeroone.lanzous.com/i0M4ndrpasd");
    $result_urldir = str_replace("index.php","","//".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?dir=true&url=https://zeroone.lanzous.com/b015k7z1a");
    echo "示例：";
    echo "<br/>";
    echo "直接下载："."<a href='".$result_url."&type=down' target='_blank'>".$result_url."&type=down</a>";
    echo "<br/>";
    echo "输出直链："."<a href='".$result_url."' target='_blank'>".$result_url."</a>";
    echo "<br/>";
    echo "带密码下载："."<a href='".$result_urlpw."&type=down&pw=f4eo' target='_blank'>".$result_urlpw."&type=down&pw=f4eo</a>";
    echo "<br/>";
    echo "带密码直链："."<a href='".$result_urlpw."&pw=f4eo' target='_blank'>".$result_urlpw."&pw=f4eo</a>";
    echo "<br/>";
    echo "文件夹列表："."<a href='".$result_urldir."&pw=233' target='_blank'>".$result_urldir."&pw=233</a>";
    echo "<br/>";
    echo "参数：<br/><br/>dir=[true,false,null] | true 文件夹操作 | false&null 文件操作 <br/><br/>{<br/>文件操作：<br/>url=(下载链接)<br/>type=[down,null] | down 下载 | null 返回直链<br/>pw=[密码]<br/>}<br/><br/>{<br/>文件夹操作：<br/>url=(下载链接)<br/>type=[list,ulist,olist,json,jsonp,count,down] | list 列出下载列表 | olist 列出蓝奏云列表 | ulist 列出直链列表 | json 返回json | jsonp 返回jsonp | count 返回文件数量 | down 直接下载（需要允许弹出窗口）【多文件时文件可能缺失，慎用】<br/>pw=[密码]<br/>jsonpname=[返回jsonp的名字]<br/>}";
}
    ?>
</html>