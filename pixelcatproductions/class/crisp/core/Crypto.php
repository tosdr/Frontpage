<?php

/*
 * Copyright (C) 2021 Justin René Back <justin@tosdr.org>
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
    private static string $Salt = 'tDSTwCkhJL4G@sMvd_zs8uS9Bq3E^stbU+9VEyXs2%cs2wxMz$4D^eYRgFJ@QEP#bKA^$!PNHt?K#@g#Fpw&!8EaucwFzt$yZ*XRmCX4fXrK5JXRMe+zaS#=#ZxGxmX?q&W$Yaur7b3FjsZ_Dr6K*pLc3LmWrdfpU@RrG-_X@93fbZB+@zg2ZQwayHy?WZag^R$vck8^E4V!wUwAMfwFW%jZVv$SqzXxFW9HJCt?2@vP8p8^M$ZbBS37T8JQ**6M';

    public static function UUIDv4($Prefix = null, $Bytes = 16): string
    {

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
        if (!hash_equals(hash_hmac('sha256', substr($ciphertext, 48) . substr($ciphertext, 0, 16), hash('sha256', self::$Salt, true), true), substr($ciphertext, 16, 32))) {
            return null;
        }
        return openssl_decrypt(substr($ciphertext, 48), "AES-256-CBC", hash('sha256', self::$Salt, true), OPENSSL_RAW_DATA, substr($ciphertext, 0, 16));
    }

}
