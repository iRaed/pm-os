<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يسمح فقط لمدراء المنصة (SaaS) بالوصول إلى لوحة الإدارة المركزية.
 *
 * NOTE: central-admin accounts are not modelled yet (the central database
 * only holds plans/tenants/domains). This guard currently requires an
 * authenticated user and treats one flagged `is_central_admin` as an admin.
 * Tighten this once a central users table / policy exists.
 */
class EnsureCentralAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403, 'Central admin access required.');
        }

        if (isset($user->is_central_admin) && ! $user->is_central_admin) {
            abort(403, 'Central admin access required.');
        }

        return $next($request);
    }
}
