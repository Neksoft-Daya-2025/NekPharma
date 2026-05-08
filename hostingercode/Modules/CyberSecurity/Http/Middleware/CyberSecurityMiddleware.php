<?php

namespace Modules\CyberSecurity\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CyberSecurity\Entities\BlacklistEmail;
use Modules\CyberSecurity\Entities\BlacklistIp;
use Modules\CyberSecurity\Entities\CyberSecurity;

class CyberSecurityMiddleware
{

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $cyberSecurity = CyberSecurity::first();

        if (
            $request->email
            && $request->isMethod('post')
            && (
                str($request->url())->contains('login')
                || str($request->url())->contains('register')
                || $request->routeIs('accept_invite')
            )
        ) {
            $userCount = User::where('register_ip', $request->ip())->whereBetween('created_at', [now()->subMinutes(5), now()])->count();

            // 1 is for if signup more then 2
            if ($userCount > 1) {
                BlacklistIp::firstOrCreate(['ip_address' => $request->ip()]);

                return response()->json(
                    [
                        'status' => 'fail',
                        'message' => __('cybersecurity::messages.blacklistIp')
                    ],
                    403
                );
            }

            if (BlacklistEmail::where('email', $request->email)->exists()) {
                if ($request->expectsJson()) {
                    return response()->json(
                        [
                            'status' => 'fail',
                            'message' => __('cybersecurity::messages.blacklistEmail')
                        ],
                        403
                    );
                }

                return redirect()->route('login')->with('message', __('cybersecurity::messages.blacklistEmail'));
            }

        }


        if (auth()->check()) {

            if ($cyberSecurity->unique_session) {
                $this->deleteOtherSessionRecords();
            }
        }

        return $next($request);
    }

    /**
     * Delete the other browser session records from storage.
     *
     * @return void
     */
    protected function deleteOtherSessionRecords()
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
            ->where('user_id', auth()->user()->getAuthIdentifier())
            ->where('id', '!=', request()->session()->getId())
            ->delete();
    }

}
