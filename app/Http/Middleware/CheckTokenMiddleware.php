<?php

namespace App\Http\Middleware;


use Closure;
use Namshi\JOSE\JWS;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Http\Middleware\Check;




class CheckTokenMiddleware extends Check
{

    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws JWTException
     * @throws TokenInvalidException
     */

    public function handle($request, Closure $next)
    {
        //$token = JWTAuth::parseToken('bearer', 'HTTP_AUTHORIZATION')->getToken();
        $token =  $this->auth->getToken();

        if (! $token) {
            throw new JWTException('A token is required', 400);
        }

        try {
            $jws = JWS::load($token);
        } catch (Exception $e) {
            throw new TokenInvalidException('Could not decode token: '.$e->getMessage());
        }

        if (! $jws->verify(config('jwt.secret'), config('jwt.algo'))) {
            throw new TokenInvalidException('Token Signature could not be verified.');
        }

        return $next($request);
    }
}
