<?php
/**
 * Library for WebApplication based on VGallery Framework
 * Copyright (C) 2004-2021 Alessandro Stucchi <wolfgan@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @package VGallery
 *  @subpackage libs
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/lgpl-3.0.html
 *  @link https://bitbucket.org/cmsff/libs
 */
namespace ff\libs\delivery\drivers;

use ff\libs\delivery\NoticeDriver;
use ff\libs\dto\DataError;
use ff\libs\Debug;
use ff\libs\Exception;
use ff\libs\storage\FilemanagerFs;

/**
 * Class MessengerTwilio
 * @package ff\libs\delivery\adapters
 */
class Firebase extends NoticeDriver
{
    public const PREFIX                 = "FIREBASE";
    public const ENDPOINT_LEGACY        = "https://fcm.googleapis.com/fcm/send";
    private const ENDPOINT              = "https://fcm.googleapis.com/v1/projects/{project_id}/messages:send";
    private const AUTH_SCOPE            = "https://www.googleapis.com/auth/firebase.messaging";
    private const AUTH_DURATION         = 3600; // Scadenza del token dopo 1 ora
    protected $apy_key                  = null;

    /**
     * @param string $message
     * @param string|null $title
     * @return DataError
     */
    public function send_legacy(string $message, string $title = null) : DataError
    {
        $msg                                = [
            'title'	    => $title,
            'body' 	    => $message,
            'sound'     => 'default',
        ];

        $payload                            = [
            'registration_ids'  => array_values($this->recipients),
            'notification'      => $msg,
        ];

        $data                               = array_filter($this->data);
        if (!empty($this->data)) {
            $payload['data']                = $data;
            $payload['content_available']   = true;
        }

        $headers = array(
            'Authorization: key=' . $this->apy_key,
            'Content-Type: application/json'
        );

        #Send Reponse To FireBase Server
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, static::ENDPOINT_LEGACY);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $result = curl_exec($ch);
        curl_close($ch);

        Debug::set($result, "firebase");
        return new DataError();
    }

    /**
     * @param string $message
     * @param string|null $title
     * @return DataError
     * @throws Exception
     */
    public function send(string $message, string $title = null) : DataError
    {
        if (empty($this->apy_key)) {
            return (new DataError())
                ->error(500, "Firebase: credentials not set");
        }

        $credentials = FilemanagerFs::fileGetContentsJson($this->apy_key);

        $payload                            = [];
        if (!empty($title) || !empty($message)) {
            $payload['notification']        = [
                'title'	    => $title,
                'body' 	    => $message,
            ];
        }
        $msg                                = [
            'title'	    => $title,
            'body' 	    => $message,
        ];

        $payload                            = [
            'notification'      => $msg,
        ];

        if (!empty($this->recipients)) {
            $token                          = array_values($this->recipients)[0];
            $to                             = strlen($token) > 64 ? "token" : "topic";

            $payload[$to]                   = $token;
        }

        $data                               = array_filter($this->data);
        if (!empty($data)) {
            $payload['data']                = $data;
        }

        if (empty($payload['notification']) && !empty($payload['data'])) {
            $payload['apns'] = [
                'payload' => [
                    'aps' => [
                        'content-available'=> 1
                    ],
                ],
            ];
        }


        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken($credentials),
            'Content-Type: application/json; UTF-8',
        ];

        #Send Reponse To FireBase Server
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, str_replace("{project_id}", $credentials->project_id, static::ENDPOINT));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["message" => $payload]));
        $result = curl_exec($ch);
        curl_close($ch);

        Debug::set($result, "firebase");
        return new DataError();
    }


    /**
     * @param object $credentials
     * @return string|null
     */
    private function getAccessToken(object $credentials) : ?string
    {
        // Creazione dell'intestazione JWT
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
            'kid' => $credentials->private_key_id
        ];

        $encodedHeader = $this->base64UrlEncode(json_encode($header));

        // Creazione dell'insieme di attestazioni JWT
        $claims = [
            'iss' => $credentials->client_email,
            'scope' => self::AUTH_SCOPE,
            'aud' => $credentials->token_uri,
            'exp' => time() + static::AUTH_DURATION,
            'iat' => time()
        ];

        $encodedClaims = $this->base64UrlEncode(json_encode($claims));

        // Creazione della stringa per la firma
        $dataToSign = $encodedHeader . '.' . $encodedClaims;

        // Firma con la chiave privata
        $signature = '';
        openssl_sign($dataToSign, $signature, $credentials->private_key, OPENSSL_ALGO_SHA256);
        $encodedSignature = $this->base64UrlEncode($signature);

        // Creazione del JWT completo
        $jwt = $encodedHeader . '.' . $encodedClaims . '.' . $encodedSignature;

        // Creazione della richiesta POST per ottenere il token di accesso
        $postData = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ];

        $ch = curl_init($credentials->token_uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        $response = curl_exec($ch);
        curl_close($ch);

        $tokenInfo = json_decode($response);

        return $tokenInfo->access_token ?? null;
    }

    /**
     * @param string $data
     * @return string
     */
    private function base64UrlEncode(string $data) : string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

