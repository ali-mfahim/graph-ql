<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;

function generateSignatureWithRaw($params)
{

    $app_secret = config("sync.pat_pat_app_secret");
    $data  = $params;
    $sortedParams = [];
    foreach ($data as $key => $value) {
        $sortedParams[] = $key . json_encode($value);
    }
    sort($sortedParams);
    $signatureFactor = join('', $sortedParams);
    $signature = strtoupper(bin2hex(hash_hmac('sha1', $signatureFactor, $app_secret, true)));
    return $signature;
}
function getAuthorizationSignature($app_id = null, $app_secret = null)
{
    $app_id = isset($app_id) && !empty($app_id) ? $app_id : config("sync.pat_pat_app_id");
    $app_secret = isset($app_secret) && !empty($app_secret) ? $app_secret : config("sync.pat_pat_app_secret");
    $signature = md5($app_id . $app_secret);
    return $signature;
}
function getAccessToken($signature = null)
{
    try {
        $signature = isset($signature) && !empty($signature) ? $signature : getAuthorizationSignature();
        $action = config("sync.pat_pat_auth_base_url") . config('sync.pat_pat_app_id') . "?_signature=" . $signature;

        $client = new Client(['verify' => false]);
        $response = $client->request("GET", $action);
        $responseBody = json_decode($response->getBody()->getContents());
        if (isset($responseBody->status) && !empty($responseBody->status)) {
            if ($responseBody->status == 200 || $responseBody->msg == "success") {
                $content = isset($responseBody->content) && !empty($responseBody->content) ? $responseBody->content : null;
                if (isset($content) && !empty($content) && isset($content->access_token) && !empty($content->access_token)) {
                    $accessToken = $content->access_token ?? null;
                    return $accessToken;
                }
            } else {
                dd($responseBody->msg);
            }
        }
    } catch (BadResponseException $e) {
        $error = json_decode($e->getResponse()->getBody());
        dd($error);
    } catch (Exception $e) {
        $error = $e->getMessage();
        dd($error);
    } catch (ClientException $e) {
        $response = $e->getResponse();
        $response = json_decode($response->getBody()->getContents());
        if (isset($response->message) && !empty($response->message)) {
            $error = $response->message ?? null;
            dd($error);
        }
    }
}

function getProductSaleInfo($params)
{
    try {
        if (isset($params) && !empty($params)) {
            $signature = generateSignatureWithRaw($params);
            $paramsArray = [
                "_signature" => $signature
            ];
            $paramsArray = array_merge($paramsArray, $params);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://open.patpat.com/api/shopify/getProductSalesInfo',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($paramsArray),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer 2RyDiQTd9mAhbHxaqxbD2tqokBcE5QGOytlkLGzZ",
                    "Content-Type: application/json"
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $result = json_decode($response);
            if (isset($result->status) && !empty($result->status) && $result->status == 200) {
                return ['success' => true, 'msg' => $result->msg, 'data' => $result->content];
            } else {
                return ['success' => false, 'msg' => $result->msg];
            }
        } else {
            return ['success' => false, 'msg' => 'Please add product ids in array'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'msg' => $e->getMessage()];
    }
}
