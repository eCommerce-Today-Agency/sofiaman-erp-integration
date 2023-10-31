<?php 

namespace App\Helpers;

use Shopify\Auth\Session;
use Shopify\Clients\Graphql;


class ERPConnectorApi {

    public const APITEST = "http://86.34.133.166:10111/OptimallWebAPItest";
    public const APIPROD = "http://86.34.133.166:10111/OptimallWebAPI";

    public static function getToken($username= '', $password = '') {

        $username = config('app.erp_username');
        $password = config('app.erp_password');
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_URL, self::APITEST . '/Token');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array('username' => $username, 'password' => $password, 'grant_type' => 'password')));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($curl);
        $res = json_decode($result);
        curl_close($curl);

        return $res->access_token;
    }

    public static function getStocPret($token, $codProdus, $gestiune){
        try {
            $postRequest = array('CodProdus' => $codProdus, 'Gestiune' => $gestiune);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_URL, self::APITEST . '/api/Data/GetStocPret');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postRequest));
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8', 'Authorization:  Bearer ' . $token]);

            $result = curl_exec($curl);
            $res = json_decode($result);
            curl_close($curl);
            return $res;
        } catch ( \Exception $e) {
            throw new \Exception($res->getBody()->__toString(), $res);
        }

    }

}