<?php

//class
class WechatCallbackapi extends Base
{
    //消息内容
    protected $arrData = array();

    //接受消息类型
    protected $arrMsgType = array(
        'text'      => array('Content'),
        'PicUrl'    => array('PicUrl'),
        'location'  => array('Location_X',   'Location_Y',   'Scale',  'Label'),
        'link'      => array('Title', 'Description', 'Url'),
        'event'     => array('Event', 'EventKey')
    );

    //回应消息类型
    protected $arrRespMsgType = array(
        'text' => array('Content'),
        'music' => array(),
        'news' => array('ArticleCount')
    );

    protected $arrRespMsgTypeWrap_music = 'Music';  

    protected $arrRespMsgTypeWrap_news = 'Articles';

    protected $arrRespMsgType_music = array(
        'Music' => array('Title', 'Description', 'MusicUrl', 'HQMusicUrl')
    );

    protected $arrRespMsgType_news = array(
        'item' => array('Title', 'Description', 'PicUrl', 'Url')
    );

    protected $ArticleCount = 0;

    protected $responseMsgContent = array();

    public function __construct($responseMsgContent=array())
    {
        parent::__construct();
   
        if (is_array($responseMsgContent) && ! empty($responseMsgContent)) {
            $this->responseMsgContent = $responseMsgContent;
        }
    }

    //验证
	public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->_checkSignature()){
        	echo $echoStr;
        	exit;
        } else {
            echo 'Hi man, no more pranks! Thx';
        }
    }

    //验证
    private function _checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];    
                
        $token = $this->config['token'];
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    //获取msg 返回内容
    public function getMsg()
    {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        if (!empty($postStr)) {
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

            $this->arrData = array(
                'ToUserName'    => $postObj->ToUserName,
                'FromUserName'  => $postObj->FromUserName,
                'CreateTime'    => $postObj->CreateTime,
                'MsgType'       => $postObj->MsgType,
                'MsgId'         => $postObj->MsgId,
            );
            $this->_getDetailMsg();
            if (count($this->arrData) > 5) {
                $this->_saveMsgToFile($arrData);
                return $this->arrData;
            }
        }
        return false;
    }

    private function _getDetailMsg()
    {
        foreach ($this->arrMsgType as $key=>$val) {
            if ($this->arrData['MsgType'] == $key) {
                foreach ($this->arrMsgType[$key] as $k=>$v) {
                    
                    $this->arrData[$v] = $postObj->$v;
          
                }
            }
        }
    }

    //保存msg
    private function _saveMsgToFile() 
    {
        if ( ! empty($this->arrData)) {
            foreach ($this->arrData as $val) {
                $arrData .= $val . "\t"; 
            }
            $arrData .= "\n";

            @file_put_contents(ROOT.'/data/' . date("Y-m-d", time()), $arrData, FILE_APPEND);
        }
    }

    //回应msg 微信服务器在五秒内收不到响应会断掉连接
    public function responseMsg($content, $msgType="text")
    {
        if ($this->_checkResponseMsg($this->responseMsgContent)) {

          	//extract post data
        	if ( ! empty($this->arrData)) {
                
                $time = time();
                $textTpl = $this->_responseMsgBaseXml();

                $textTpl .= $this->_responseMsgDetailXml($this->responseMsgContent, $this->responseMsgContent['MsgType']);

        		$textTpl .=	$this->_responseMsgBaseEndXml();
        		if ( ! empty($this->arrData['Content']))
                {
                	// TODO
                    $resultStr = sprintf($textTpl, $this->arrData['fromUsername'], $this->arrData['toUsername'], $time, $msgType, $contentStr);
                	echo $resultStr;
                }

            } else {
            	echo "";
            	exit;
            }

        }
    }

    //回应参数配置
    public function _checkResponseMsg() 
    {
        if (empty($this->responseMsgContent['ToUserName'])) {
            return false;
        }
        if (empty($this->responseMsgContent['FromUserName'])) {
            return false;
        }
        if (empty($this->responseMsgContent['MsgType'])) {
            return false;
        }

        //文本消息
        if ($this->responseMsgConten['MsgType'] == 'text') {
            if (empty($this->responseMsgContent['Content'])) {
               return false;
            }
        }
        //音乐消息
        if ($this->responseMsgConten['MsgType'] == 'music') {
            if (empty($this->responseMsgContent['Music'])) {
               return false;
            } else {
                foreach ($this->arrRespMsgType_music as $key=>$val) {
                    if (empty($this->responseMsgContent[$key][$val])) {
                       return false;
                    }
                }
            }
        }
        //图文消息
        if ($this->responseMsgConten['MsgType'] == 'news') {
            if (empty($this->responseMsgContent['Articles'])) {
               return false;
            } else {
               foreach ($this->arrRespMsgType_news as $key=>$val) {
                    if (empty($this->responseMsgContent[$key][$val])) {
                       return false;
                    }
                }
            }
        }

        return true;
    }

    //拼接基础xml
    private function _responseMsgBaseXml() 
    {
        return "<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[%s]]></MsgType>";
    }
    
    //拼接基础结束xml
    private function _responseMsgBaseEndXml() 
    {
        return "<FuncFlag>0</FuncFlag>
                </xml>";
    }

    //拼接详细xml
    private function _responseMsgDetailXml()
    {
        $textTpl = '' ;
        if (in_array($this->arrData['MsgType'], $this->arrRespMsgType)) {
            foreach ($this->arrRespMsgType as $key=>$val) {

                $arrTmpName = 'arrRespMsgType_' .$val;
                $arrTmpWrapName = 'arrRespMsgTypeWrap_' .$val;

                $textTpl = "<" . $arrTmpWrapName . ">";
                if (is_array($arrTmpName)) {

                    if (empty($val)) {
                        //回复音乐消息
                        foreach ($this->arrTmpName as $k=>$v) {
                            $textTpl .= "<" . $v . "<![CDATA[%s]]></ " . $v .">";
                        }
                    } else {
                        //回复图文消息
                        if ($this->responseMsgContent['MsgType'] == 'news') {
                            
                            foreach ($this->arrTmpName as $k=>$v) {
                                $textTpl .= "<" . $k . ">";
                                for ($i=0; $i<$this->responseMsgContent['ArticleCount']; $i++) {
                                    $textTpl .= "<" . $v . "<![CDATA[%s]]></" . $v .">";
                                }
                                $textTpl .= "</" . $k . ">";
                            }
                        }
                    }

                } else {
                    //回复文本消息
                    $textTpl = "<". $val ."><![CDATA[%s]]></". $val .">";

                }

                $textTpl .= "</" . $arrTmpWrapName . ">";
            }
        }
        return $textTpl;
    }

}

?>
