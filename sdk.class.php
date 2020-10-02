<?php
/**
 * Class Payjs
 * 作者: dudu
 * 本代码无版权，可以随意复制、修改使用
 */

class Payjs
{

    private $url = 'https://payjs.cn/api/native';

    public function pay()
    {
        global $_G, $payjs;
        if ($_POST['money'] <= 0) return 'money error';

        $out_trade_no = date('YmdHis') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $data         = array(
            'mchid'        => $payjs['mchid'],
            'total_fee'    => $_POST['money'] * 100,
            'out_trade_no' => $out_trade_no,
            'ip'           => $_SERVER['REMOTE_ADDR'],
            'notify_url'   => trim($_G['siteurl'] . 'source/plugin/payjs/notify.php'),
        );
        $data['sign'] = $this->sign($data);

        $this->insert($data);
        $result = $this->httpPost($this->url, $data);
        return $result;
    }

    public function insert($arr)
    {
        global $_G, $payjs;
        $data = array(
            'orderid'    => $arr['out_trade_no'],
            'status'     => 1,
            'uid'        => $_G['uid'],
            'amount'     => $arr["total_fee"] / 100 * $payjs['integral_proportion'],
            'price'      => $arr["total_fee"] / 100,
            'submitdate' => time(),
            'ip'         => $_SERVER['REMOTE_ADDR'],
        );

        C::t('forum_order')->insert($data);
        return;
    }

    public function check()
    {
        $orderid = $_GET['orderid'];
        $order   = DB::fetch_first("select * from " . DB::table('forum_order') . " where orderid='" . $orderid . "' and status=2");
        if ($order) {
            return 'paid';
        } else {
            return 'notpaid';
        }
    }

    public function sign($arr)
    {
        global $payjs;
        array_filter($arr);
        ksort($arr);
        $sign = strtoupper(md5(urldecode(http_build_query($arr) . '&key=' . $payjs['key'])));
        return $sign;
    }

    public function httpPost($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Discuz Plugin CLIENT');
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

}

?>
