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


}