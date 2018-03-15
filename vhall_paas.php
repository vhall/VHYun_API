<?php

/**
 * vhall API 快速接入函数封装
 * Class VhallPaas
 */
class VhallPaas
{
    // 由微吼云颁发，可通过控制台->设置->应用信息中获取
    static public $app_id;
    static public $secret_key;

    /**
     * 计算签名
     * @param $params
     * @return string
     */
    public static function sign($params){
        // 按参数名升序排列
        ksort($params);

        // 将键值组合
        array_walk($params,function(&$value,$key){
            $value = $key . $value;
        });

        // 拼接,在首尾各加上$secret_key,计算MD5值
        $sign = md5(self::$secret_key . implode('',$params) . self::$secret_key);

        return $sign;
    }

    /**
     * 计算最终请求数据
     * @param $params
     * @return mixed
     */
    public static function createRealParam($params)
    {
        if (isset($params['sign'])) return $params;

        // 补充公共参数
        if (!isset($params['app_id'])) {
            $params['app_id'] = self::$app_id;
        }
        if (!isset($params['signed_at'])) {
            $params['signed_at'] = time();
        }

        // 计算签名
        if (!isset($params['sign'])) {
            $params['sign'] = self::sign($params);
        }

        return $params;
    }

    /**
     * 请求接口
     * @param $address
     * @param $param
     * @return mixed
     * @throws Exception
     */
    public static function request($address, $param)
    {
        $result = self::curlPost($address, $param);
        $result = json_decode($result, true);

        // 当code不存在是被认为是请求异常，请联系微吼云技术人员
        if (!isset($result['code'])) {
            throw new Exception("请求异常");
        }

        if ($result['code'] != 200) {
            throw new Exception($result['msg'], $result['code']);
        }

        return $result['data'];
    }


    /**
     * curl 请求
     * @param $url
     * @param $data
     * @param int $timeOut
     * @return bool|mixed
     */
    private static function curlPost($url, $data, $timeOut = 3)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeOut);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $resultData = curl_exec($curl);

        if (curl_errno($curl)) {
            curl_close($curl);
            return false;
        } else {
            curl_close($curl);
            return $resultData;
        }
    }


}
