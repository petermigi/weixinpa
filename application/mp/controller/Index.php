<?php

namespace app\mp\controller;

use think\Controller;

class Index extends Controller
{

    //各种类型响应消息的模板
    private $tpl = array(
        'text' => ' <xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[%s]]></Content>                    
                    </xml>',
        'image' => '<xml>
                    <ToUserName>< ![CDATA[%s] ]></ToUserName>
                    <FromUserName>< ![CDATA[%s] ]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType>< ![CDATA[image] ]></MsgType>
                    <Image>
                    <MediaId>< ![CDATA[%s] ]></MediaId>
                    </Image>                       
                    </xml>',
        'list' => ' <xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[news]]></MsgType>
                    <ArticleCount>%s</ArticleCount>
                    <Articles>
                    %s
                    </Articles>
                    </xml>', 
        //图文响应消息            
        'item' => ' <item>
                    <Title><![CDATA[%s]]></Title> 
                    <Description><![CDATA[%s]]></Description>
                    <PicUrl><![CDATA[%s]]></PicUrl>
                    <Url><![CDATA[%s]]></Url>
                    </item>',
        //音乐响应消息
        'music' => '<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[music]]></MsgType>
                    <Music>
                    <Title><![CDATA[%s]]></Title>
                    <Description><![CDATA[%s]]></Description>
                    <MusicUrl><![CDATA[%s]]></MusicUrl>
                    <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
                    <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
                    </Music>
                    </xml>'
);   

    //接入微信服务器的入口
    public function index()
    {
        //$this->traceHttp();
        /* if(array_key_exists('echostr',$_GET))
        {
            
            $this->valid();

        }
        else
        {
            $this->responseMsg();
        } */ 
        
        // 获得参数 signature nonce token timestamp
        $nonce = $_GET['nonce'];
        $token = 'imooc';
        $timestamp = $_GET['timestamp'];
        //$echostr = $_GET['echostr'];
        $signature = $_GET['signature'];

        //形成数组, 然后按字典序排序
        $array = array();
        $array = array($nonce, $timestamp, $token);
        sort($array,SORT_STRING);
        //拼接成字符串,sha1加密, 然后与signature进行校验
        $str = sha1(implode($array));
        if($str == $signature)
        {
            //第一次与微信服务器接入的情况
            //第一次接入weixin api接口的时候, 微信服务器会多传一个参数echostr
            //可以根据有没有echostr判断是不是第一次接入
            //file_put_contents('testdebug.txt','this is a debug test');
            if(array_key_exists('echostr',$_GET))
            {
                $echostr = $_GET['echostr'];
                echo $echostr;
                exit;
            }
            else
            {
                //不是与微信服务器第一次接入的情况
                //file_put_contents('test.html','this is a debug test'."<br>", FILE_APPEND);
                $this->responseMsg();
            }
            
        }
        else
        {
            exit;    
        }    

    }

    //追踪查看来访的ip地址,特别是微信服务器ip地址的来访
    public function traceHttp()
    {
        $this->logger("REMOTE_ADDR:".$_SERVER["REMOTE_ADDR"].((strpos($_SERVER['REMOTE_ADDR'],"140.207")+1)?" From WeiXin":" Unknown IP"));
        $this->logger("QUERY_STRING:".$_SERVER['QUERY_STRING']);

    }

    public function logger($content)
    {
        file_put_contents("log.html", date('Y-m-d H:i:s  ').$content."<br/>", FILE_APPEND);
    }

    //微信服务器与第三方服务器接入的三个方法(微信官方接入示例代码wx_sample.php中的三个方法)
    //valid(),checkSignature(),reponseMsg()
    //方法1 valid()
    //微信服务器与第三方服务器第一次连接验证双方身份的方法valid()
    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
    }

    //方法2 checkSignature() 签名验证    
    private function checkSignature()
	{
        //接收微信服务器发送的get请求参数: signature timestamp nonce
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
		$token = 'imooc'; //自己的服务器定义的TOKEN常量
        $tmpArr = array($token, $timestamp, $nonce);
        //将token timestamp nonce进行字典排序
        sort($tmpArr, SORT_STRING);
        //转换为字符串
        $tmpStr = implode( $tmpArr );
        //sha1加密
        $tmpStr = sha1( $tmpStr );        
                   
		//判断自己服务器端用token,timestamp,nonce形成的signature与微信服务器发送过来的signature是否相同
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
    }

   //方法3 responseMsg() 响应的消息
   public function responseMsg() //所有的被动消息处理都从这里开始
   {
       
       //file_put_contents('testdebug00.txt','this is a debug test');
       //get post data, May be due to the different environments
       //$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];//获得微信用户发送的请求消息(xml数据格式)
       $postStr = file_get_contents("php://input");
       //将获得的微信用户请求消息(xml数据格式)转换为php的对象
       $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
       //file_put_contents('testdebug.txt', $postObj);
      
       //判断请求消息的类型(MsgType标签),判断微信用户的的行为:
       //是发送了文本消息还是图片消息或者语音消息亦或点击了底部一级菜单等
       //以便第三方服务器端作出合适的响应
       switch($postObj->MsgType)
       {
           case 'event':                
               $this->_doEvent($postObj);
               break;
           case 'text':
               $this->_doText($postObj);
               break;
           case 'image':
               $this->_doImage($postObj);
               break;
           case 'voice':
               $this->_doVoice($postObj);
               break;
           case 'video':
               $this->_doVideo($postObj);
               break;
           case 'location':
               $this->_doLocation($postObj);
               break;
           case 'shortvideo':
               $this->_doShortVideo($postObj);
               break;
           case 'link':
               $this->_doLink($postObj);
               break;
           default: exit;
       }
   }

   //事件处理,先判断事件类型(Event标签),是订阅关注公众号事件
    //还是取消订阅关注公众号事件等
    //或者是点击了底部一级菜单的click事件
    //再做对应的响应处理
    private function _doEvent($postObj)
    {
        switch($postObj->Event)
        {
           case 'subscribe': //订阅关注                
               $this->_doSubscribe($postObj);
               break;
           case 'unsubscribe': //取消订阅关注
               $this->_doUnsubscribe($postObj);
               break;
           case 'CLICK': //自定义菜单事件click                
               $this->_doClick($postObj);
               break;
           default:;
        }        
    }

    //各种事件类型的响应处理
     //订阅关注事件的处理
     private function _doSubscribe($postObj)
     {          
        $str = sprintf($this->tpl['text'],$postObj->FromUserName,$postObj->ToUserName,time(),'欢迎您关注PHP Weixin39 世界20181216! ');
        echo $str;
        
     }

     //回复微信用户请求消息的类型(MsgType)为文本消息的处理
    //微信用户向公众号发送了文本消息的处理
    private function _doText($postObj)
    {
        //接收请求消息的数据信息
        $fromUsername = $postObj->FromUserName; //微信用户openId
        $toUsername = $postObj->ToUserName;  //开发者微信号
        $keyword = trim($postObj->Content);  //请求消息的内容
        $time = time(); //请求消息发送的时间

        //响应消息的数据设置           
        if(!empty( $keyword ))
        {
            if(mb_substr($keyword,0,2,'utf-8') == '歌曲')
            {
                $this->_sendMusic($postObj);
            }

            //默认响应消息的内容,默认自动回复的消息
            $contentStr = "欢迎来到PHP39这里! ";

            if($keyword == "PHP")
            {
                $contentStr = "最流行的网页编程语言! ";
            }    

            if($keyword == "JAVA")
            {
                $contentStr = "比较流行的网页编程语言! ";
            }    

            //响应消息的类型(MsgType标签)
            $msgType = "text";   
            //将响应消息的数据加载渲染到响应模板中
            //其中$fromUsername为微信用户openId, $toUsername为//开发者微信号 像写信一样     	
            $resultStr = sprintf($this->tpl['text'], $fromUsername, $toUsername, $time, $contentStr);

            //返回响应消息给微信服务器,微信服务器会将我们的响应消息处理给微信用户
            echo $resultStr;
            
        } 
        exit;
    }

    //封装的https接口请求方法_request
    private function _request($curl, $https=true,$method='get',$data=null)
    {
        $ch = curl_init(); //初始化
        curl_setopt($ch,CURLOPT_URL,"$curl"); //设置访问的URL
        curl_setopt($ch,CURLOPT_HEADER,FALSE); //设置不需要头信息
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); //只获取页面内容,但不输出
        curl_setopt($ch,CURLOPT_SAFE_UPLOAD,false); //解决php5.6版本以上不支持微信接口curl '@文件路径' 的问题

        //https处理
        if($https)
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不做服务器认证
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //不做客户端认证        
        }

        if($method == 'post')
        {
            curl_setopt($ch, CURLOPT_POST, true); //设置请求是POST方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data); //设置POST请求的数据
        }

        $str = curl_exec($ch); //执行访问,返回结果
        curl_close($ch); //关闭curl,释放资源   
        
        return $str;
    }

    //微信公众号网页授权 基础授权scope权限域为snsapi_base 只能拿到网页授权的access_token和openid
    //先引导微信公众号用户(必须关注了这个公众号的用户)到这个页面方法getBaseInfo()中,利用微信客户端扫二维码的功能
    //把进入到getBaseInfo()这个方法的url制作成二维码,让微信用户扫这个二维码
    //就引导微信公众号用户进入到这个页面方法getBaseInfo()中了
    //接着用header方法往获取code的微信服务器接口跳转
    //跳转后会携带返回的code重定向到我们定义的$redirect_uri参数对应的getUserOpenId(方法中)
    public function getBaseInfo()
    {
        //1. 获取到code
        $appid = "wx36fa59f034d2994a";
        $redirect_uri = urlencode('http://www.ecook.top/mp/Index/getUserOpenId');
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
        header('location:'.$url);

    }

    public function getUserOpenId()
    {
        //2.获取到网页授权的access_token和openid
        //把携带code的自动重定向进入到这个getUserOpenId()方法中的code获得
        //并向获取网页授权的access_token和openid的微信服务器接口发起请求
        $appid = "wx36fa59f034d2994a";
        $appsecret = "f94522623e5196f606016cf2281d4c67";
        $code = $_GET['code'];
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";

        //3.拉取用户的opendid
        $res = $this->_request($url);
        var_dump($res);    
        //$openid = $res['openid'];
        
    }

     //微信公众号网页授权 高级授权scope权限域为snsapi_userinfo 只能拿到网页授权的access_token和openid
    //原理与基础授权相同
    //只不过要修改scope权限域为snsapi_userinfo
    //拿到通过code拿到access_token和openid后
    //去请求获取微信公众号用户详细信息的微信服务器接口
    //来获得微信用户的详细信息
    public function getUserDetail()
    {
        //1. 获取到code
        $appid = "wx36fa59f034d2994a";
        $redirect_uri = urlencode('http://www.ecook.top/mp/Index/getUserInfo');
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
        header('location:'.$url);
    }

    public function getUserInfo()
    {
        //2.获取到网页授权的access_token和openid
        //把携带code的自动重定向进入到这个getUserOpenId()方法中的code获得
        //并向获取网页授权的access_token和openid的微信服务器接口发起请求
        $appid = "wx36fa59f034d2994a";
        $appsecret = "f94522623e5196f606016cf2281d4c67";
        $code = $_GET['code'];
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
        
        //发起请求获取用户的access_token和openid
        $res = $this->_request($url);
        //var_dump($res);    
        //$access_token = $res['access_token'];
        //$openid = $res['openid'];
        
        //注意微信服务器接口返回的是json格式的字符串
        //要在php语言中使用,必须用json_decode()函数转化为php对象或数组
        $res = json_decode($res,true);
        $access_token = $res['access_token'];
        $openid = $res['openid'];
        
        //echo $openid;die();

        //3.拉取用户的详细信息
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        $res = $this->_request($url);
        var_dump($res);

    }




}//class end