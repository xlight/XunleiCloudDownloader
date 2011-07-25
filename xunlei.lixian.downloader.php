#!/opt/bin/php
<?php       
error_reporting(E_ALL ^ E_NOTICE);                                                                                                                                                                        
$u = '88280500';                                                                                                                                                                        
$p = 'cdawrtc516';                                                                                                                                                                      

//$downloadPath = dirname(__FILE__);
//$downloadPath = '/opt/incoming';
$downloadPath = '/mnt';
$wgetCmd = '/opt/bin/wget';

/* start */
if(!runlock()){
echo "[".date('Y-m-d H:i:s')."]runlock! \n";
 exit (10);
}
$tasklist = getCloudList();

if($tasklist['result'] != 0){
	loginprogress($u,$p);
	$tasklist = getCloudList();
}
$tasklist = $tasklist['list']['records'];

if(!$tasklist) {echo "[".date('Y-m-d H:i:s')."]get CloudList Fail!\n" ; die(2);}

touchNclean($tasklist,$downloadPath);

foreach($tasklist as $task){

        $filename = $task['taskname'];                                                                                                                                             
        $url = $task['lixian_url'];          
                                                                                                                                              
        if(file_exists("{$downloadPath}/{$filename}") ){
         	echo "[".date('Y-m-d H:i:s')."]already exists, skip : {$filename}  \n";
         	continue;  
        }
        
        $downloadedSize = file_exists("{$downloadPath}/{$filename}.download") ? filesize("{$downloadPath}/{$filename}.download") : 0;
        if(disk_free_space($downloadPath) < ($task['dst_file_size'] - $downloadedSize ) ){
        	echo "[".date('Y-m-d H:i:s')."]not enough space: {$filename}    filesize: {$task['dst_file_size']} need: ".($task['dst_file_size'] - $downloadedSize -disk_free_space($downloadPath) ) ."\n";
        	continue;
        }                                                                                                              
        if($downloadedSize != $task['dst_file_size']){                                                                                            
              echo "[".date('Y-m-d H:i:s')."]download : ".$filename."\n";   
              $cmd = "{$wgetCmd}  -c --load-cookies=".dirname(__FILE__).'/cookie.txt '
                 ." '{$url}' ". " -O '{$downloadPath}/{$filename}.download' ";
              $retVal = '';
              system($cmd,$retVal);
        }
        clearstatcache();
        if(filesize("{$downloadPath}/{$filename}.download") == $task['dst_file_size']){    
        	echo "[".date('Y-m-d H:i:s')."]done{$retVal} : {$filename} \n";                                                                                        
              rename("{$downloadPath}/{$filename}.download", "{$downloadPath}/{$filename}");                                                                                     
        }else{
        	echo "[".date('Y-m-d H:i:s')."]NotDone{$retVal} : {$filename} Complete:".filesize("{$downloadPath}/{$filename}.download")."/{$task['dst_file_size']}\n";
        }
}

/*
echo "generate index.htm ...";
$Content = '';
$files = glob($downloadPath.'/*');
foreach($files as $file){
$Content .= "<a href='".basename($file).'>'.basename($file).'</a>';
}
file_put_contents($downloadPath.'/index.htm',$Content);
echo "OK\n";
//*/
unset($lock);
echo "[".date('Y-m-d H:i:s')."]Done\n";


//==============
function touchNclean($tasklist,$downloadPath){

$downloadingList = glob($downloadPath.'/*.download');
foreach($downloadingList as &$i) $i = basename($i,'.download');
$downloadingList = array_flip($downloadingList);

foreach($tasklist as $task){
 $filename = $task['taskname'];
 unset($downloadingList[$filename]);
 if(!file_exists($downloadPath.'/'.$filename))
   touch($downloadPath.'/'.$filename.'.download');
 

}

foreach($downloadList as $i){
 rename($downloadPath.'/'.$i , $downloadPath.'/'.$i.'.crop');
echo "[".date('Y-m-d H:i:s')."]rename crop file: {$i}\n";
}

}

function getCloudList(){
echo '['.date('Y-m-d H:i:s').']getCloudList ... ';
$tasklist = array();

$page = 1;
do{
 $datetime = date('D M d Y H:i:s \G\M\TO (T)');
 $url = 'http://dynamic.cloud.vip.xunlei.com/interface/get_cloud_list?t='.urlencode($datetime).'&p='.$page;
 $Contents = request::get($url);

 $Contents = json_decode($Contents,1);
 if(0 != $Contents['result']) echo "error  ";  
 
 $max_page = $Contents['max_page'];
 //$total_num = $Contents["total_num"];
 $tasklist = array_merge($tasklist , $Contents['list']['records'] );
 $page++;
}while($page <= $max_page);

usort($tasklist,'cmp');
$Contents['list']['records'] = $tasklist;  
echo "OK\n";

return $Contents ;

}
function cmp($a, $b)
{
	if ($a['taskname'] == $b['taskname']) {
		return 0;
	}
	return ($a['taskname'] < $b['taskname']) ? -1 : 1;
}

function loginprogress($u,$p){


echo "[".date('Y-m-d H:i:s')."]getVerifyCode ... ";                                                                                                                                                                
$verifycode = getVerifyCode($u);
if($verifycode) echo $verifycode," OK\n";
else echo "Fail\n";

$parameters = getLoginParam($u,$p,$verifycode);                                                                                                                                                                             

//var_dump($parameters);


echo "[".date('Y-m-d H:i:s')."]Login ... ";
if(0 != ($errCode = login($parameters) ) ) {
	echo "Fail: ".getErrMsg($errCode)."\n";
	exit(1);
}
echo "OK\n";


echo "[".date('Y-m-d H:i:s')."]getIn ... ";
$url = 'http://dynamic.lixian.vip.xunlei.com/login?cachetime='.time().rand(100,999).'&cachetime='.time().rand(100,999).'&from=0';
$mainContents = request::get($url);
if($mainContents)echo "OK\n";
else echo "Fail\n";


}

function getLoginParam($u,$p,$verifycode){

$login_enable = 1;                                                                                                                                                                      
$login_hour = '720';                                                                                                                                                                    
                                                                                                                                                                                    
$p = md5(md5($p));                                                                                                                                                                      
$p = md5($p . strtoUpper($verifycode) );                                                                                                                                                
                                                                                                                                                                                        
return $parameters = array(    'u'=>$u,                                                                                                                                                      
                        'p'=>$p,                                                                                                                                                      
                        'verifycode'=> strtoupper($verifycode),                                                                                                                         
                        'login_enable'=>$login_enable,                                                                                                                                  
                        'login_hour'=>$login_hour,                                                                                                                                      
                        );
}
function getVerifyCode($u){
	$url = 'http://login.xunlei.com/check?u='.$u;
	request::get($url);
	
	$cookies = request::get_cookie();

	$a = explode(':',$cookies['check_result']);

	return trim($a[1]);
	
}

function login($parameters){

	$url = 'http://login.xunlei.com/sec2login/';
	request::post($url,$parameters);
	
	$cookies = request::get_cookie();
	//var_dump($cookies);die;
	return trim($cookies['blogresult']);

}
function getErrMsg($code){
	$error = array(1=>"验证码错误",2=>"密码错误",3=>"服务器忙",4=>"帐号不存在",5=>"帐号不存在",6=>"帐号被锁定",7=>"服务器忙",8=>"服务器忙",9=>"非法验证码",10=>"非法验证码",11=>"验证码超时",12=>"登录页面无效",13=>"登录页面无效",14=>"登录页面无效",15=>"登录页面无效",16=>"网络超时，请重新登录");

return $error[$code];
}
      


function runLock(){
	$cmd = 'pidof '.basename(__FILE__);
	$pids = system($cmd);
	//var_dump($pids);
	if(strval(getmypid()) == $pids){
		return true;
	}
	return false;
}



class request{
	static $cookiefile;
	static $curlOptions;                                                                                                                                                                                                
	function init(){
		self::$cookiefile = dirname(__FILE__).'/cookie.txt';

		self::$curlOptions = "  --compressed -s -L -b ".self::$cookiefile." -c ".self::$cookiefile . " ";
		self::$curlOptions .= ' -o '.dirname(__FILE__).'/tmp';
		//self::$curlOptions .= ' -D/dev/stdout ';		


	}
	function get($url){
		//echo $url ."\n";
		self::init();
		$cmd = "curl ".self::$curlOptions." '{$url}' ";
		
		system($cmd);
		return file_get_contents(dirname(__FILE__).'/tmp');
		
	}
	function post($url,$param)   {
		self::init();
	
		$postData = " -d '".http_build_query($param) ."'";
		$cmd = "curl ".self::$curlOptions." {$postData} '{$url}' ";
		system($cmd);
		return file_get_contents(dirname(__FILE__).'/tmp');
	}
	
	function get_cookie() { 
		$file = self::$cookiefile;
		$aCookies = array(); 
		$aLines = file($file); 
		if(!$aLines) return false;
		foreach($aLines as $line){ 

	  		if('#'==$line{0}) 
				continue; 
		$arr = explode("\t", $line); 
		if(isset($arr[5]) && isset($arr[6])) 
			$aCookies[$arr[5]] = trim($arr[6]); 
		} 
	
		return $aCookies; 
	}     
}    
