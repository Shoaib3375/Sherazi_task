<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MultiTenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (!$tenantId) {
            return response()->json(['error' => 'Tenant ID is required'], 400);
        }

        // এন্টারপ্রাইজ লেভেলে এখানে আমরা গ্লোবাল স্কোপ বা ডাটাবেজ কানেকশন চেঞ্জ করতাম।
        // ডেমোর জন্য আমরা এটাকে রিকোয়েস্টে রেখে দিচ্ছি যা কন্ট্রোলারে ইউজ করা যাবে।
        $request->merge(['tenant_id' => $tenantId]);

        return $next($request);
    }
}
