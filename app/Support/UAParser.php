<?php

namespace App\Support;

class UAParser
{
    public static function parse(?string $ua): ?object
    {
        if (!$ua) return null;

        return (object) [
            'browser' => self::browser($ua),
            'os'      => self::os($ua),
            'device'  => self::device($ua),
            'raw'     => $ua,
        ];
    }

    private static function browser(string $ua): string
    {
        if (preg_match('/Edg\/(\d+)/', $ua, $m))             return 'Edge ' . $m[1];
        if (preg_match('/OPR\/(\d+)/', $ua, $m))             return 'Opera ' . $m[1];
        if (preg_match('/SamsungBrowser\/(\d+)/', $ua, $m))  return 'Samsung Browser ' . $m[1];
        if (preg_match('/Firefox\/(\d+)/', $ua, $m))         return 'Firefox ' . $m[1];
        if (preg_match('/Chrome\/(\d+)/', $ua, $m))          return 'Chrome ' . $m[1];
        if (preg_match('/Version\/(\d+).*Safari/', $ua, $m)) return 'Safari ' . $m[1];
        if (preg_match('/curl\/([^\s]+)/', $ua, $m))         return 'curl ' . $m[1];
        if (preg_match('/python-requests\/([^\s]+)/', $ua, $m)) return 'python-requests ' . $m[1];
        if (preg_match('/Go-http-client\/([^\s]+)/', $ua, $m))  return 'Go HTTP ' . $m[1];
        if (preg_match('/axios\/([^\s]+)/', $ua, $m))           return 'axios ' . $m[1];
        if (preg_match('/node-fetch\/([^\s]+)/', $ua, $m))      return 'node-fetch ' . $m[1];

        // Truncate unknown UAs to first meaningful token
        $first = explode(' ', trim($ua))[0];
        return strlen($first) > 30 ? substr($first, 0, 30) . '…' : $first;
    }

    private static function os(string $ua): string
    {
        if (preg_match('/Windows NT (\d+\.\d+)/', $ua, $m)) {
            $map = ['10.0' => 'Windows 10/11', '6.3' => 'Windows 8.1', '6.2' => 'Windows 8', '6.1' => 'Windows 7'];
            return $map[$m[1]] ?? 'Windows';
        }
        if (preg_match('/iPhone OS ([\d_]+)/', $ua, $m))   return 'iOS ' . str_replace('_', '.', $m[1]);
        if (preg_match('/CPU OS ([\d_]+)/', $ua, $m))      return 'iOS ' . str_replace('_', '.', $m[1]);
        if (preg_match('/Android (\d+)/', $ua, $m))        return 'Android ' . $m[1];
        if (preg_match('/Mac OS X ([\d_]+)/', $ua, $m))    return 'macOS ' . str_replace('_', '.', $m[1]);
        if (str_contains($ua, 'Linux'))                     return 'Linux';
        return '';
    }

    private static function device(string $ua): string
    {
        if (str_contains($ua, 'iPad'))                              return 'Tablet';
        if (str_contains($ua, 'iPhone'))                            return 'Mobile';
        if (preg_match('/Android/', $ua) && str_contains($ua, 'Mobile')) return 'Mobile';
        if (preg_match('/Android/', $ua))                           return 'Tablet';
        if (preg_match('/Windows|Macintosh|Linux/', $ua))           return 'Desktop';
        return '';
    }
}
