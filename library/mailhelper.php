<?php

/**
 * 定义了发邮件通用的静态方法集
 *
 * @author youbeiliang01@baidu.com
 * @date 2014-08-05
 */
final class mailhelper {
    /*
      Mail服务联系人：方盼<footballboyfp> 14:30:36
      mail-out.sys.baidu.com 事务性邮件通道   MailFrom = app_notice@baidu.com
      edm.sys.baidu.com 推广类邮件通道          MailFrom = app_publish@baidu.com
      注意：邮件服务器都使用ssl协议
     */

    /**
     * 发送事务类邮件
     * @author youbeiliang01@baidu.com
     * @date 2014-08-05
     *
     * @param string $title 邮件标题
     * @param string $content 邮件内容
     * @param bool $ishtml 是否html邮件
     * @param string $mailTo 多个邮箱以半角逗号分隔
     * @param string $attachments
     *      附件地址（绝对路径或相对于入口文件的相对路径）, 多个附件以半角逗号分隔
     * @param type $addTip 是否自动补上备注内容
     * @return string
     *   'ok' 表示发送成功
     *   其它返回值，表示发送失败的错误信息
     */
    static function send_notice($title, $content, $ishtml, $mailTo, $attachments = '', $addTip = true) {
        //if(ENVIRONMENT !== 'production')
        //    $mailTo='jhao9000@163.com';

        $CI = & CI_Controller::get_instance();
        //$CI->load->library('email');

        $mailServer = $CI->config->item('MAIL_NOTICE_SERVER');
        $mailPort = $CI->config->item('MAIL_NOTICE_PORT');
        $mailFrom = $CI->config->item('MAIL_NOTICE_FROM');
        $mailPwd = $CI->config->item('MAIL_NOTICE_PWD');

        if (ENVIRONMENT == 'production') {
            return self::sendMail($title, $content, $ishtml, $mailTo, $mailServer, $mailPort, $mailFrom, $mailPwd, $attachments, $addTip);
        } else {
            return self::sendMailByBdSmtp($title, $content, $mailTo, $attachments);
        }
    }

    /**
     * @brief 新增开发环境使用百度邮件类库
     * @author liting16@baidu.com
     * @date 2017-06-05
     * @param $sTitle
     * @param $sContent
     * @param $mailTo
     * @param $attachments
     * @return string
     */
    private function sendMailByBdSmtp($sTitle, $sContent, $mailTo, $attachments) {
        if (empty($sTitle) || empty($mailTo)) {
            return false;
        }

        $oSmtp = new Bd_Smtp();
        $oSmtp->setFrom("mobile.open@baidu.com");
        $oSmtp->addAddress($mailTo);
        $oSmtp->addBccAddress(array(
                )
        );

        if (is_array($attachments) && !empty($attachments)) {
            foreach ($attachments as $attachment) {
                $oSmtp->addAttachment($attachment);
            }
        }

        $bStatus = $oSmtp->send($sTitle, $sContent, false);

        return $bStatus == true ? 'ok' : $oSmtp->getErrMsg();
    }

    /**
     * 发送推广类邮件
     * @author youbeiliang01@baidu.com
     * @date 2014-08-05
     *
     * @param string $title 邮件标题
     * @param string $content 邮件内容
     * @param bool $ishtml 是否html邮件
     * @param string $mailTo 多个邮箱以半角逗号分隔
     * @return string
     *   'ok' 表示发送成功
     *   其它返回值，表示发送失败的错误信息
     */
    static function send_publish($title, $content, $ishtml, $mailTo) {
        $CI = & CI_Controller::get_instance();

        $mailServer = $CI->config->item('MAIL_PUBLISH_SERVER');
        $mailPort = $CI->config->item('MAIL_PUBLISH_PORT');
        $mailFrom = $CI->config->item('MAIL_PUBLISH_FROM');
        $mailPwd = $CI->config->item('MAIL_PUBLISH_PWD');

        return self::sendMail($title, $content, $ishtml, $mailTo, $mailServer, $mailPort, $mailFrom, $mailPwd);
    }

    /**
     * 使用指定的服务器和发件人发邮件
     * @author youbeiliang01@baidu.com
     * @date 2014-08-05
     *
     * @param string $title 邮件标题
     * @param string $content 邮件内容
     * @param bool $ishtml 是否html邮件
     * @param string $mailTo 多个邮箱以半角逗号分隔
     * @param type $mailServer
     * @param type $mailPort
     * @param type $mailFrom
     * @param type $mailPwd
     * @param type $attchments
     * @param type $addTip 是否自动补上备注内容
     *      附件地址（绝对路径或相对于入口文件的相对路径）, 多个附件以半角逗号分隔
     * @return string
     *   'ok' 表示发送成功
     *   其它返回值，表示发送失败的错误信息
     */
    static function sendMail($title, $content, $ishtml, $mailTo, $mailServer, $mailPort, $mailFrom, $mailPwd, $attchments = '', $addTip = true) {
        $CI = & CI_Controller::get_instance();

        $config = Array(
            'protocol' => 'smtp',
            'smtp_host' => $mailServer,
            'smtp_port' => $mailPort,
            'smtp_user' => $mailFrom,
            'smtp_pass' => $mailPwd,
            'mailtype' => $ishtml ? 'html' : 'text',
            'newline' => "\r\n"
        );

        $CI->load->library('email', $config);
        $config2['mailtype'] = $ishtml ? 'html' : 'text';
        $CI->email->initialize($config2);
        $CI->email->from($mailFrom, '百度移动开放平台');
        $CI->email->to($mailTo);
        $CI->email->subject($title);
        $resultContent = $content;
        if ($addTip) {
            $resultContent = $content . "<br/><br/>注：自动发送邮件，请勿直接回复，如果有问题请发邮件到开发者服务邮箱：ext_app_support@baidu.com 进行咨询。";
        }
        $CI->email->message($resultContent);

        // 添加附件
        //$path = $this->config->item('server_root');
        //$file = $path . '/ci/attachments/yourinfo.txt';
        if (!empty($attchments)) {
            foreach (explode(',', $attchments) as $path) {
                $path = trim($path);
                if (!empty($path) && file_exists($path)) {
                    $CI->email->attach($path);
                }
            }
        }

        if ($CI->email->send()) {
            return 'ok';
        }

        return $CI->email->print_debugger();
    }

    /**
     * 发送短消息,切换到百度通道
     *
     * @author youbeiliang01@baidu.com
     * @date 2015-11-09
     *
     * @return true false
     */

    /**
     * 发送短信验证码，其它短信不能使用
     * @param type $msgContent 短信内容
     * @param type $msgDest 接收手机号,多个电话以半角逗号分隔
     * @param type $log 接口返回的内容，用于外部log
     * @return boolean
     */
    static function sendMsgCode($msgContent, $msgDest, &$log = null) {
        $username = 'app.baidu';
        $password = 'baidu.app-dev';
        $businessCode = 'app_baidu_com1';

        return self::sendMsgBaidu($username, $password, $businessCode, $msgContent, $msgDest, $log);
    }

    /**
     * 发送通知类的短信
     * @param type $msgContent 短信内容
     * @param type $msgDest 接收手机号,多个电话以半角逗号分隔
     * @param type $log 接口返回的内容，用于外部log
     * @return boolean
     */
    static function sendMsgNotice($msgContent, $msgDest, &$log = null) {
        $username = 'app.baidu';
        $password = 'baidu.app-dev';
        $businessCode = 'mdev_baidu_com1';

        return self::sendMsgBaidu($username, $password, $businessCode, $msgContent, $msgDest, $log);
    }

    /**
     * 发送百度通道的短信。
     * 接口文档：http://wiki.baidu.com/pages/viewpage.action?pageId=34282421
     * 新的文档：http://wiki.baidu.com/pages/viewpage.action?pageId=40201174
     */
    private static function sendMsgBaidu($username, $password, $businessCode, $msgContent, $msgDest, &$log = null) {
        // 测试环境：http://emsgtest.baidu.com/service/sendSms.json
        // 线上环境：http://emsg.baidu.com/service/sendSms.json
        if (ENVIRONMENT == 'production') {
            $url = 'http://emsg.baidu.com/service/sendSms.json';     //调用接口的平台服务地址
        } else {
            $url = 'http://emsgtest.baidu.com/service/sendSms.json';  //调用接口的平台服务地址
        }

        $msgContent = strhelper::cutstr($msgContent, 200);
        //$msgContent = substr($msgContent, 0, 256);
        $signature = md5($username . $password . $msgDest . $msgContent . $businessCode);

        $curlPost = 'username=' . $username;
        $curlPost .= '&businessCode=' . $businessCode;
        $curlPost .= '&msgContent=' . $msgContent;
        $curlPost .= '&msgDest=' . $msgDest;
        $curlPost .= '&signature=' . $signature;

        $ch = curl_init();
        $this_header = array(
            "content-type: application/x-www-form-urlencoded; charset=UTF-8"
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this_header);
        curl_setopt($ch, CURLOPT_URL, $url);
        // 是否要把头信息输出
        //curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        //curl_setopt($ch, CURLOPT_VERBOSE, true);

        $strResult = curl_exec($ch);
        curl_close($ch);

        self::_logMsg($msgContent, $msgDest, $strResult);
        if (empty($strResult)) {
            return false;
        }
        $result = json_decode($strResult);

        if (!empty($result) && !empty($result->result) && $result->result == 1000) {
            // 记录成功日志
            LogUtil::logmsg($url . PHP_EOL . $curlPost . PHP_EOL . $strResult, 'msg/ok');
            return true;
        }
        // 记录错误日志，错误码说明：http://wiki.baidu.com/pages/viewpage.action?pageId=40201260
        LogUtil::logmsg($url . PHP_EOL . $curlPost . PHP_EOL . $strResult, 'msg/err');
        return false;
    }

    /**
     * 发送短信后的日志记录
     * @param type $msgContent 短信内容
     * @param type $msgDest 手机号
     * @param type $ret 接口返回
     * @param type $isok 是否成功
     */
    private static function _logMsg($msgContent, $msgDest, $ret) {
        $CI = & CI_Controller::get_instance();
        $CI->load->model('model_commonlog');
        $content = $msgContent . PHP_EOL . $ret;

        if (preg_match_all('/\d+/', $msgDest, $matchs)) {
            foreach ($matchs[0] as $phone) {
                $CI->model_commonlog->insertLogUtil($phone, 400, $content);
            }
        }
    }

}
