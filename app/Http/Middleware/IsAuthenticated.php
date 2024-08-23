<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAuthenticated
{
    private static function checkJwt(Request $request): ?JsonResponse
    {
        list($header, $payload, $signature) = explode('.', $request->bearerToken());

        $decodedPayload = json_decode(self::base64_url_decode($payload), true);
        if(!$decodedPayload){
            return response()->json(['status' => false,"message"=>"Invalid Token"], 401);
        }

        if(!isset($decodedPayload['email'])){
            return response()->json(['status' => false,"message"=>"Invalid Token"], 401);
        }

        //check if the token is expired
        if($decodedPayload['exp'] < time()){
            return response()->json(['status' => false,"message"=>"Token Expired, Please login to Continue"], 401);
        }

        //store the email in session
        session()->put('email',$decodedPayload['email']);

        return null;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Retrieve the Bearer token from the request headers, as it's the 3rd party api so we can only ale to check weather authorization is present
        $token = $request->bearerToken();

        //if login then exclude the token check
        if($request->path() == 'api/v1/auth/login'){
            return $next($request);
        }

        // Check if the Bearer token is present
        if ($token) {
            // If the token is present, proceed to the next middleware
            $response = self::checkJwt($request);
            if ($response instanceof Response) {
                return $response;
            }
            return $next($request);
        }
        // If the token is not present, return an unauthorized response
        return response()->json(['status' => false,"message"=>"UnAuthorized Access, Please login to Continue"], 401);

    }

     private static function base64_url_decode($data): false|string
     {
        $data = strtr($data, '-_', '+/');
         return base64_decode($data);
    }




}
