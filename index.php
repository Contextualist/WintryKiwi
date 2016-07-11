<?php
function mobile_check(){
        $mobile_browser = false;
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $accept = $_SERVER['HTTP_ACCEPT'];
        if(
			preg_match('/(acs|alav|alca|amoi|audi|aste|avan|benq|bird|blac|blaz|brew|cell|cldc|cmd-|dang|doco|erics|hipt|inno|ipaq|java|jigs|kddi|keji|leno|lg-c|lg-d|lg-g|lge-|maui|maxo|midp|mits|mmef|mobi|mot-|moto|mwbp|nec-|newt|noki|opwv|palm|pana|pant|pdxg|phil|play|pluc|port|prox|qtek|qwap|sage|sams|sany|sch-|sec-|send|seri|sgh-|shar|sie-|siem|smal|smar|sony|sph-|symb|t-mo|teli|tim-|tosh|tsm-|upg1|upsi|vk-v|voda|w3cs|wap-|wapa|wapi|wapp|wapr|webc|winw|winw|xda|xda-|up.browser|up.link|windowssce|iemobile|mini|mmp|symbian|midp|wap|phone|pocket|mobile|pda|psp)/i',$user_agent) || 
			((strpos($accept,'text/vnd.wap.wml')>0)||(strpos($accept,'application/vnd.wap.xhtml+xml')>0))
		){
                $mobile_browser = true;
        }
        return $mobile_browser;
}
if(substr($_SERVER['REQUEST_URI'],1,4)!="wiki" and substr($_SERVER['REQUEST_URI'],0,3)!="/w/")
	$req_uri = "/".base64_decode(substr($_SERVER['REQUEST_URI'],1));
else
	$req_uri = $_SERVER['REQUEST_URI'];
if ($req_uri == "/"){
	$req_uri = "/wiki/Wikipedia:%E9%A6%96%E9%A1%B5";
	$loc = "zh";
}else{
	if(preg_match("/\!([\w\-]+)/",  $req_uri, $matches)){
		$loc = $matches[1];
		$req_uri=str_replace("!".$loc,"",$req_uri);
	}else
		$loc = "zh";
}
$host = ".wikipedia.org";
if(mobile_check()){
	$host = ".m" . $host;
}
$host = $loc. $host;
$real_http_req_url = "https://".$host.$req_uri;
$ch = curl_init(); 
curl_setopt($ch, CURLOPT_URL, $real_http_req_url); 
if($_SERVER['REQUEST_METHOD'] == 'POST'){
	curl_setopt($ch, CURLOPT_POST, 1); 
	$body = @file_get_contents('php://input');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
}
$headers = array();
foreach ($_SERVER as $k => $v) {
	if (substr($k, 0, 5) == "HTTP_") {
		$k = str_replace('_', ' ', substr($k, 5));
		$k = str_replace(' ', '-', ucwords(strtolower($k)));
		if (strtolower($k) == "host")
			$v = $host;
		else if (strtolower($k) == "referer")
			$v = $real_http_req_url;
		else if (strtolower($k) == "accept-encoding")
			continue;
		array_push($headers, $k.": ".$v);
	}
}
array_push($headers, "Accept-Encoding: gzip,deflate");
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
//curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$resp_header = substr($response, 0, $header_size);
$body = substr($response, $header_size);
$resp_compress = 0;
$is_html_content = true;
$is_image_content = false;
foreach(explode("\r\n", $resp_header) as $_){
	if (strtolower(substr($_, 0, 10)) == "set-cookie") {
		header(str_replace(".wikipedia.org",".".$_SERVER['HTTP_HOST'],$_));
	} else if(strtolower(substr($_,0,8))=="location"){
		preg_match("/([\w\-]+).[m.]*wikipedia.org\/(.+)/i",$_,$_m);
		if($_m[1])
			header("Location: /".base64_encode($_m[2]."!".$_m[1]), false);
	}else if(strtolower(substr($_,0,16))=="content-encoding"){
		if(strpos($_, 'gzip'))
			$resp_compress = 1;
		else if(strpos($_, 'deflate'))
			$resp_compress = 2;
	}else if(strtolower(substr($_,0,12))=="content-type"){
		if(strpos($_, 'text/html') === FALSE){
			$is_html_content = false;
			if(strpos($_, 'image'))
				$is_image_content = true;
		}
		header($_, false);
	}else{
		header($_, false);
	}
}
curl_close($ch); //get data after login 

if($resp_compress >0)
	$body = gzdecode($body);

if (!$is_image_content){
	//$pattern = "@zh.wikipedia.org/w/load.php\?([^\"]+)@i";
	//$body = preg_replace($pattern, $_SERVER['HTTP_HOST'].'/w/load.php?\\1', $body);
	$pattern = "@zh.wikipedia.org/([^\"]+)@i";
	$body = preg_replace($pattern, $_SERVER['HTTP_HOST'].'/\\1', $body);
}
if (strpos($req_uri, "load.php") === FALSE){ 
	echo $body;
	if ($is_html_content){
?>
<script type="text/javascript" src="http://v3.jiathis.com/code/jiathis_r.js?move=0&amp;btn=r3.gif" charset="utf-8"></script>
<script src="/base64.js"></script>
<script type="text/javascript">
var is_http = location.protocol=="http:";
var uri = Base64.decode(document.location.pathname.substr(1));
if(uri.indexOf('!')!=-1){
	var loc="!"+uri.substr(uri.indexOf('!')+1);}
else{
	var loc="";
}
if(is_http){
	var objs=document.getElementsByTagName("a");
	for(var i=0;i<objs.length;i++){
		if(objs[i].lang=="" && objs[i].href.indexOf(window.location.host)!=-1 && objs[i].href.indexOf('#')==-1){
			objs[i].href="/"+Base64.encode(objs[i].href.substring(objs[i].href.indexOf("/wiki/")+1)+loc)
		}
	};
}
function encode_langlink(n){
	return Base64.encode(n.href.substring(n.href.indexOf('wikipedia.org')+14)+'!'+n.hreflang);
}
<?php if(mobile_check()) echo "
var _footer=document.getElementsByClassName('footer-info');
if(_footer)_footer=_footer[0];
if(is_http){
	var f=document.forms[1];
	var f1=document.forms[2];
	var other_lang=$('#mw-mf-language-selection').children();
	var zhconv=$('#mw-mf-language-variant-selection').children();
	$('#mw-mf-display-toggle').href=
	'/'+Base64.encode('/w/index.php?title='+encodeURIComponent($('#section_0').innerHTML)+'&mobileaction=toggle_view_desktop');
}
"; else echo "
var _footer=$('#footer-info-copyright');
if(_footer)_footer=_footer[0];
if(is_http){
	var f=$('#searchform');
	var f1=$('#search');
	var other_lang=$('.interlanguage-link');
	var zhconv = $('#p-variants').children('div').find('a');
};
"; ?>
/*var other_lang=document.getElementsByClassName('page-list');other_lang=other_lang[other_lang.length-1].children;if(other_lang){other_lang=other_lang.children;for(var i=0;i<other_lang.length;i++){var n=other_lang[i].children[0];n.href=Base64.encode(n.href.substring(n.href.indexOf('wikipedia.org')+14)+'!'+n.hreflang);}}*/
document.title = document.title.replace('Wikipedia', 'Wikimirror').replace('维基百科，自由的百科全书', '维基百科镜像，不撞墙的百科全书');
if(other_lang){
	for(var i=0;i<other_lang.length;i++){
		var n=other_lang[i].children[0];
		n.href=Base64.encode(n.href.substr(n.href.indexOf('wikipedia.org')+14)+'!'+n.hreflang);
	}
}
if(zhconv){
	console.log(zhconv);
	for(var i=0;i<zhconv.length;i++){
		zhconv[i].href = "/"+Base64.encode(zhconv[i].href.replace(location.protocol+"//"+location.host+"/", ""));
	}
}
if(_footer) _footer.innerHTML+="<hr><br><b>维基镜像(其实是个反代)的内容完全来自维基百科，在HTTP明文传输时对链接进行了简单加密。</b><br>相关源代码可以从<a href='https://gist.github.com/fffonion/9048776' target='_blank'>这里获得</a> 登陆注册编辑功能目前不可用。";
if(is_http){
	f.setAttribute("onsubmit","return b64redirect(true);");
	if(f1) f1.setAttribute("onsubmit","return b64redirect(false);");
	function b64redirect(isSimple){
		if(f.search.value){
			if(isSimple)
				window.location="/"+Base64.encode("/w/index.php?search="+f.search.value+"&title="+f.title.value+"&go="+f.go.value);
			else
				window.location="/"+Base64.encode("/w/index.php?search="+f1.search.value+"&profile=default&fulltext=search&title="+f1.title.value);
		}
		return false;
	};//mobile:other-lang-list, search not encoded; goto desktop not functioning
}
</script>
<?php
	}
} else {
	header("Cache-Control: max-age=3600, must-revalidate");
	header("Expires: " . gmdate("D, d M Y H:i:s", time() + 3600) . " GMT");
	echo $body;
};?>
