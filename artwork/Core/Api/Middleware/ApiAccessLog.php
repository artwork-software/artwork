<?php

namespace Artwork\Core\Api\Middleware;

use Artwork\Core\Api\Models\ApiAccessToken;
use Artwork\Core\Api\Models\ApiLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiAccessLog
{
    public function handle(Request $request, \Closure $next)
    {
        $apiKey = Str::after($request->header('authorization', ''), "Bearer ");
        $tokenId = ApiAccessToken::where('access_token', $apiKey)->firstOrFail()->id;
        $apiLog = new ApiLog();
        $apiLog->token_id = $tokenId;
        $apiLog->ip = $request->ip();
        $apiLog->method = $request->method();
        $apiLog->url = $request->url();
        $apiLog->api_key = $apiKey;
        $apiLog->user_agent = $request->userAgent();
        $apiLog->payload = $request->getContent();
        $apiLog->save();

        return $next($request);
    }
}
