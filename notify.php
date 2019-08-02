<?php
header("Content-type: text/html; charset=utf-8");
require '../../class/class_core.php';
require '../../function/function_forum.php';
$discuz = C::app();
$discuz->init();
loadcache('plugin');
$payjs = $_G['cache']['plugin']['payjs'];

checkSign($_POST);

$orderid = $_REQUEST['out_trade_no'];

$order = DB::fetch_first("select * from " . DB::table('forum_order') . " where orderid='" . $orderid . "' and status=1");
if ($order) {
    // 更新订单状态
    $data  = array('status' => 2, 'confirmdate' => time());
    $where = array('orderid' => $orderid);
    DB::update('forum_order', $data, $where);

    // 更新用户积分
    updatemembercount($order['uid'], array($_G['setting']['creditstrans'] => $order['amount']), true, '', 1, '', '微信支付充值');

    // 积分消息提醒
    notification_add($order['uid'], 'system', 'addfunds', array(
        'orderid'     => $order['orderid'],
        'price'       => $order['price'],
        'from_id'     => 0,
        'from_idtype' => 'buycredit',
        'value'       => $_G['setting']['extcredits'][$_G['setting']['creditstrans']]['title'] . ' ' . $order['amount'] . ' ' . $_G['setting']['extcredits'][$_G['setting']['creditstrans']]['unit'],
    ), 1);
}

echo 'success';

function checkSign($arr)
{
    global $payjs;
    $user_sign = $arr['sign'];
    unset($arr['sign']);
    array_filter($arr);
    ksort($arr);
    $check_sign = strtoupper(md5(urldecode(http_build_query($arr) . '&key=' . $payjs['key'])));

    if ($user_sign != $check_sign)
        die('签名错误');
}

?>
