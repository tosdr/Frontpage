<?php

/*
 * Copyright 2020 Pixelcat Productions <support@pixelcatproductions.net>
 * @author 2020 Justin René Back <jback@pixelcatproductions.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace crisp\core;

/**
 * Interact with the encryption system behind LophotenCMS
 */
class Crypto {

    /**
     * NICHT ÄNDERN
     * @var string
     */
    private static $Salt = 'tDSTwCkhJL4G@sMvd_zs8uS9Bq3E^stbU+9VEyXs2%cs2wxMz$4D^eYRgFJ@QEP#bKA^$!PNHt?K#@g#Fpw&!8EaucwFzt$yZ*XRmCX4fXrK5JXRMe+zaS#=#ZxGxmX?q&W$Yaur7b3FjsZ_Dr6K*pLc3LmWrdfpU@RrG-_X@93fbZB+@zg2ZQwayHy?WZag^R$vck8^E4V!wUwAMfwFW%jZVv$SqzXxFW9HJCt?2@vP8p8^M$ZbBS37T8JQ**6M';

    public static function UUIDv4($Prefix = null, $Bytes = 16) {

        $data = random_bytes($Bytes);
        return $Prefix . vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function encrypt($plaintext, $encoding = null) {
        $iv = openssl_random_pseudo_bytes(16);
        $ciphertext = openssl_encrypt($plaintext, "AES-256-CBC", hash('sha256', self::$Salt, true), OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext . $iv, hash('sha256', self::$Salt, true), true);
        return $encoding == "hex" ? bin2hex($iv . $hmac . $ciphertext) : ($encoding == "base64" ? base64_encode($iv . $hmac . $ciphertext) : $iv . $hmac . $ciphertext);
    }

    public static function decrypt($ciphertext, $encoding = null) {
        $ciphertext = $encoding == "hex" ? hex2bin($ciphertext) : ($encoding == "base64" ? base64_decode($ciphertext) : $ciphertext);
        if (!hash_equals(hash_hmac('sha256', substr($ciphertext, 48) . substr($ciphertext, 0, 16), hash('sha256', self::$Salt, true), true), substr($ciphertext, 16, 32)))
            return null;
        return openssl_decrypt(substr($ciphertext, 48), "AES-256-CBC", hash('sha256', self::$Salt, true), OPENSSL_RAW_DATA, substr($ciphertext, 0, 16));
    }

}
