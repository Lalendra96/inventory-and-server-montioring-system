<?php

namespace App\Http\Middleware;

use App\Services\FeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function __construct(
        private readonly FeatureService $features
    ) {
    }

    public function handle(
        Request $request,
        Closure $next,
        string $feature
    ): Response {
        abort_unless($this->features->enabled($feature), 404);

        return $next($request);
    }
}
