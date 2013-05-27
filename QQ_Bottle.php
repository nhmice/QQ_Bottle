<?php
//====================================
//       QQ邮箱漂流瓶
//  http://weibo.com/weird
//====================================
$uid = '87555190';
$pw = '123456';
$plpfrom ='webmail';//网页版webmail 移动版wapmail
$content ='请根据你的要求回复: 1，聊天 /微笑 2，交朋友 /握手 3，约炮 /害羞 4，路过 /大兵';
$appid = '522005705';//qq邮箱内部app编号

//step 1
$data = http('https://ssl.ptlogin2.qq.com/check?uin='.$uid.'&appid='.$appid.'&ptlang=2052&js_type=2&js_ver=10009');
preg_match_all("@'([^']+)'@",$data['body'],$r);
$vcode = $r[1][1];
$msg = $r[1][2];
$cookie .= get_cookie($data['header']);
//step 2
$send = array(
  'ptlang'=>'2052',
	'aid'=>$appid,
	'daid'=>'4',
	'u1'=>'https://mail.qq.com/cgi-bin/login?vt=passport&vm=wpt&ft=ptlogin&ss=&validcnt=&clientaddr='.$uid.'@qq.com',
	'from_ui'=>'1',
	'ptredirect'=>'1',
	'h'=>'1',
	'wording'=>'快速登录',
	'css'=>'https://mail.qq.com/zh_CN/htmledition/style/fast_login148203.css',
	'mibao_css'=>'m_ptmail',
	'u_domain'=>'@qq.com',
	'uin'=>$uid,
	'u'=>$uid.'@qq.com',
	'p'=>qq_pwd($pw,$msg,$vcode),
	'verifycode'=>$vcode,
	'fp'=>'loginerroralert',
	'action'=>'3-6-33036',
	'g'=>'1',
	't'=>'1',
	'dummy'=>'',
	'js_type'=>'2',
	'js_ver'=>'10009'
);
$data = http('https://ssl.ptlogin2.qq.com/login?'.http_build_query($send, '', '&'),false,$cookie);
preg_match_all("@'([^']+)'@",$data['body'],$r);
if($r[1][0]){
	echo '登录失败-需要验证码';
	exit();
}
$url = $r[1][2];
$cookie .= get_cookie($data['header']);

//step3 登录成功，跳转
$data = http($url,false,$cookie);
$cookie .= get_cookie($data['header']);
$url = '';
foreach($data['header'] as $v){
	if(!strncasecmp('Location: ', $v, 10)){
		$url = substr($v, 10);
		break;
	}
}

//step 4 再次跳转
$data = http($url,false,$cookie);
$url = '';
foreach($data['header'] as $v){
	if(!strncasecmp('Location: ', $v, 10)){
		$url = substr($v, 10);
		break;
	}
}
$cookie .= get_cookie($data['header']);
$sid  = '';
if($url == '' ){
	preg_match('@sid=([^"]+)@',$data['body'],$r);
	if($r){
		$sid = $r[1];
	}else{
		echo '登录失败2';
		exit();
	}
}else{
	preg_match('@sid=([^&]+)@',$url,$r);
	$sid = $r[1];
}

if(empty($sid)){
	echo '登录失败3';
	exit();
}
//登录成功
//====================获取自己瓶子资料===============
$data = http('http://mail.qq.com/cgi-bin/bottle_panel?sid='.$sid.'&t=bottle&plpfrom=webmail&loc=folderlist,,,33',false,$cookie);
preg_match('@city\s+:\s+\\\x27(\d+)\\\x27@i',$data['body'],$r);
$city = $r[1];
preg_match('@age\s+:\s+\\\x27(\d+)\\\x27@i',$data['body'],$r);
$age = $r[1];
preg_match('@sex\s+:\s+\\\x27(.*?)\\\x27@',$data['body'],$r);
$sex = mb_convert_encoding($r[1],'utf-8','gbk');

//====================丢瓶子===========
$send = array(
	"mailid"=>"",
	"sendcount"=>"-1",
	"content"=>$content,
	"remarkB"=>"",
	"qqicon"=>"true",
	"bottle_type"=>"8",
	"image"=>"",
	"talkimage"=>"",
	"uin"=>"",
	"action"=>"loc",
	"action2"=>"",
	"copy"=>"1",
	"region"=>$city,
	"same"=>"1",
	'eSex'=>$sex=='女' ? '男' : '女',
	'eAge'=>$age,//年龄
	"selfcity"=>$city,
	"address"=>"南宁,",
	"searchaddress"=>"广西-南宁",
	"selfsex"=>$sex,
	"city"=>$city,
	"latlng"=>"0,0",
	"replyret"=>"",
	"sid"=>$sid,
	"plpfrom"=>$plpfrom,
	"resp_charset"=>"UTF8",
	"ef"=>"js"
);

for($i=0;$i<10;$i++){
	http('http://mail.qq.com/cgi-bin/bottle_send',$send,$cookie);
}

//====================捞瓶子============
$data = http('http://mail.qq.com/cgi-bin/bottle_attr',array('action'=>'getgainlist',
'page'=>0,'pagesize'=>100,'sid'=>$sid,'plpfrom'=>$plpfrom,'resp_charset'=>'UTF8','ef'=>'js','region'=>$city),$cookie);

$json = decode($data['body']);
//发送 
if($json){
	foreach($json as $k=>$u){
		if($u['gender'] != $sex && $city==$u['region']){
			$send = array(
				'mailid'=>'',
				'sendcount'=>'',
				'content'=>$content,
				'remarkB'=>'',
				'qqicon'=>'true',
				'bottle_type'=>'21',
				'image'=>'',
				'talkimage'=>'',
				'uin'=>$k,
				'action'=>'talktoonline',
				'action2'=>'',
				'selfsex'=>$sex,
				'region'=>$city,
				'replyret'=>'',
				'sid'=>$sid,
				'plpfrom'=>$plpfrom,
				'resp_charset'=>'UTF8',
				'ef'=>'js'
			);
			$data = http('http://mail.qq.com/cgi-bin/bottle_send',$send,$cookie);
		}
	}
}

//===============相关函数=============
/*
* qq邮箱加密方法
*/
function qq_pwd($p,$pt,$vc){
    $p = strtoupper(md5($p));
    $len = strlen($p);
    $temp = '';
    for ($i=0; $i < $len ; $i = $i + 2){
        $temp .= '\x'.substr($p, $i,2);
    }
    return strtoupper(md5(strtoupper(md5(hex2asc($temp).hex2asc($pt))).$vc));
}
function hex2asc($str){
    $str = join('', explode('\x', $str));
    $len = strlen($str);
    $data = null;
    for ($i=0;$i<$len;$i+=2)
    {
        $data .= chr(hexdec(substr($str,$i,2)));
    }
    return $data;
}

function http($url,$data=false,$cookie=''){
	$context = array(
    'http' => array(
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.152 Safari/537.22\r\nReferer: https://mail.qq.com/cgi-bin/loginpage\r\n".(empty($cookie) ? '' : "Cookie: {$cookie}\r\n"),
        ),
    );
	if($data){
		$context['http']['method'] = 'POST';
        $context['http']['content']  = http_build_query($data, '', '&');
	}
	$cx = stream_context_create($context);
	return array(
		'body'=>file_get_contents($url, false, $cx),
		'header'=>$http_response_header
	);
}
function cookie_str2arr($str) {
        $ret = array();
        $cookies = explode(';', $str);
        $ext = array('path','expires','domain','httponly','');
        if(count($cookies)) {
            foreach($cookies as $cookie) {
				$cookie = trim($cookie);
				$arr = explode('=', $cookie);
				//找出值
				$value = implode('=',array_slice($arr,1,count($arr)));;
				$k = trim($arr[0]);
				if(!in_array(strtolower($k), $ext)){
					$ret[$k] = $value;
				}
			  }
        }
        return $ret;
}

function get_cookie($header){
	$cookies = array();
	foreach($header as $line){
		if(!strncasecmp('Set-Cookie: ', $line, 12)){
			$cookies = array_merge($cookies,cookie_str2arr(substr($line, 12)));
		}
	}
	$cookie = '';
    foreach ($cookies  as $k=>$v) {
         $cookie .= $k.'='.$v.';';
    }
	return $cookie;
}
function decode($json) 
{  
	$json = str_replace("'", '"', $json);
	$json=preg_replace('/\s+/', '',$json);
    $comment = false; 
    $out = '$x='; 
    
    for ($i=0; $i<strlen($json); $i++) 
    { 
        if (!$comment) 
        { 
            if ($json[$i] == '{')        $out .= ' array('; 
            else if ($json[$i] == '}')    $out .= ')'; 
            else if ($json[$i] == ':')    $out .= '=>'; 
            else                         $out .= $json[$i];            
        } 
        else $out .= $json[$i]; 
        if ($json[$i] == '"')    $comment = !$comment; 
    } 
	if($out){
		eval($out . ';'); 
	}
    return $x; 
}
?>
