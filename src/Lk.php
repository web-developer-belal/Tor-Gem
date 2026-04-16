<?php

namespace Synthora\Gem;

class Lk
{
    public static function c1($url, $domain, $uid, $key)
    {
        $post = http_build_query([
            'domain' => $domain,
            'uid'    => $uid,
            'key'    => $key,
        ]);

        $r = self::c2($url, $post);
        if ($r !== null) return $r;

        $r = self::c3($url, $post);
        if ($r !== null) return $r;

        $r = self::c4($domain);
        if ($r !== null) return $r;

        return null;
    }

    protected static function c2($url, $post)
    {
        if (!function_exists('curl_init')) return null;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200 && $res) {
            return json_decode($res, true);
        }
        return null;
    }

    protected static function c3($url, $post)
    {
        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $post,
                'timeout' => 5,
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ];
        $ctx = stream_context_create($opts);
        $res = @file_get_contents($url, false, $ctx);
        if ($res !== false) {
            return json_decode($res, true);
        }
        return null;
    }

    protected static function c4($domain)
    {
        if (!function_exists('dns_get_record')) return null;
        $recs = @dns_get_record("_elixer.{$domain}", DNS_TXT);
        if (empty($recs)) return null;
        foreach ($recs as $rec) {
            $txt = $rec['txt'] ?? '';
            parse_str(str_replace(';', '&', $txt), $parts);
            if (isset($parts['v']) && $parts['v'] == '1') {
                return [
                    'status' => $parts['status'] ?? 'fail',
                    'data'   => [
                        'uid' => $parts['uid'] ?? null,
                        'key' => $parts['key'] ?? null,
                    ]
                ];
            }
        }
        return null;
    }
}