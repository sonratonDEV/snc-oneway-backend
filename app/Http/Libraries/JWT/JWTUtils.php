<?php

namespace App\Http\Libraries\JWT;

use App\Http\Libraries\JWT\JWT;
use App\Http\Libraries\JWT\Key;

// define("PRIVATE_KEY", "<Secret Key>");
define("PRIVATE_KEY", "MIIBOwIBAAJBAOG2phaFv23XSjPK1c3/n6h4MQRvGDrIcsVOVztCKXKmSdknsZ7k
cJu5hFKjK1BC200MX1T8TeC7LwBF0IgbC1UCAwEAAQJAaRzeC+8NTuHLtILpPp2k
hJg0e5Qg/H8Ms8Xf6cDN/I2EQuAdRTiUFaYGz5jv7zX8PZN4f3WVcjwTrIcsGiUX
oQIhAPCwf/4LoHQ70rxABFqL3ao1sqCripAmYa37hKQDJSfbAiEA8BJF3QAgJD+9
vm4EezV7cPGkC2ddCDbhildEn9v72I8CIQCYQ9B08v4LCl2C4ize62TL7TSYGG2M
S/u3iUqcR7EVyQIgGoR6MRIdesOLquguGInsy6n1S8ksDoc5XHWNP/cll2MCIQC8
qSEXqqgdZtD0f8bJ37CjubVWmlRSbcDYYPSAUkQMmg==");

class JWTUtils
{
     public function generateToken($payload)
     {
          $token = JWT::encode($payload, PRIVATE_KEY, 'HS256');
          return $token;
     }

     public function verifyToken($header)
     {
          $token = null;
          // extract the token from the header
          if (!empty($header)) {
               if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                    $token = $matches[1];
               }
          }

          // check if token is null or empty
          if (is_null($token) || empty($token)) {
               return (object)['state' => false, 'msg' => 'Access denied', 'decoded' => []];
          }

          try {
               $decoded = JWT::decode($token, new Key(PRIVATE_KEY, 'HS256'));
               return (object)['state' => true, 'msg' => 'OK', 'decoded' => $decoded];
          } catch (\Exception $e) {
               return (object)['state' => false, 'msg' => $e->getMessage(), 'decoded' => []];
          }
     }
}
