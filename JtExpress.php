<?php

/**
 * User: xionglonghua
 * Date: 2018/9/4
 * Time: 下午3:11
 */

namespace xionglonghua\express;

use \Yii;
use yii\caching\Cache;
use yii\base\InvalidParamException;

class JtExpress extends \lspbupt\curl\CurlHttp
{
    private $key;
    private $username;
    private $apiKey;

    public function init()
    {
        if (isset(Yii::$app->params['express']['jnt']['key'])) {
            $this->key = Yii::$app->params['express']['jnt']['key'];
        }
        if (isset(Yii::$app->params['express']['jnt']['username'])) {
            $this->username = Yii::$app->params['express']['jnt']['username'];
        }
        if (isset(Yii::$app->params['express']['jnt']['API_KEY'])) {
            $this->apiKey = Yii::$app->params['express']['jnt']['API_KEY'];
        }

        $this->afterRequest = function($output, $curlhttp) {
            $data = json_decode($output, true);
            if(empty($output)) {
                $data = [
                    'errcode' => 1,
                    'errmsg' => '网络错误!',
                ];
            }
            return $data;
        };
    }

    /**
     * 查询快递信息
     * @param int $billcode
     * @return mixed
     */
    public function getOrderInfo($billcode = 0)
    {
        if (isset(Yii::$app->params['express']['jnt']['trackingHost'])) {
            $this->host = Yii::$app->params['express']['jnt']['trackingHost'];
        } else {
            throw new InvalidParamException("Please configure param: trackingHost");
        }
        if (!$billcode) {
            throw new InvalidParamException("Please configure billcodes.");
        }
        $data_request = [
            'billcode' => $billcode,
            'lang'     => 'id',
            'pictype'  => 'sj,yn,lc,qs',
            'sign'     => md5(date('Ymd').'YnTrackQuery'.$billcode),
        ];
        return $this->setPost()->httpExec('/jandt_track/trackToJson.action', $data_request);
    }

    /**
     * 快递下单
     * @param $params
     * @return mixed
     */
    public function onlineOrder($params)
    {
        if (isset(Yii::$app->params['express']['jnt']['orderHost'])) {
            $this->host = Yii::$app->params['express']['jnt']['orderHost'];
        } else {
            throw new InvalidParamException("Please configure param: orderHost");
        }
        if (!isset($params['weight'])){
            throw new InvalidParamException("Please configure param: weight");
        }
        //单位转换，前端传参单位默认为克(g)， jnt单位默认为千克(kg)
        $params['weight'] = $params['weight'] / 1000;
        $data = [
            'username' => $this->username,
            'api_key'  => $this->apiKey,
        ];
        $data = array_merge($data, $params);
        $data_json = json_encode(['detail'=> [$data]]);
        $data_request = [
            'data_param'=>$data_json,
            'data_sign'=> base64_encode(md5($data_json.$this->key)),
        ];
        return $this->setPost()->httpExec('/JandT_ecommerce/api/onlineOrder.action', $data_request);
    }

    /**
     * 取消订单
     * @param int $billCode
     * @param string $remark
     * @return mixed
     */
    public function cancelOrder($orderId = 0, $remark = '')
    {
        if (isset(Yii::$app->params['express']['jnt']['cancelHost'])) {
            $this->host = Yii::$app->params['express']['jnt']['cancelHost'];
        } else {
            throw new InvalidParamException("Please configure param: cancelHost");
        }
        $remark = empty($remark) ? 'Canceled by '.$this->username : $remark;
        $data = [
            'username' => $this->username,
            'api_key'  => $this->apiKey,
            'orderid'  => $orderId,
            'remark'   => $remark,
        ];
        $data_json = json_encode(['detail'=> [$data]]);
        $data_request = [
            'data_param'=>$data_json,
            'data_sign'=> base64_encode(md5($data_json.$this->key)),
        ];
        return $this->setPost()->httpExec('/JandT_ecommerce/api/cancelOrder.action', $data_request);
    }

}
