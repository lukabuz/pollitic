<?php

namespace App\Http\Middleware;

use Closure;

class CrawlerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {   
        $crawlers = [
            'facebookexternalhit/1.1',
            'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
            'Facebot',
            'Twitterbot',
        ];

        if(str_contains($request->header('User-Agent'), $crawlers)){
            $route = explode('/', $request->path());

            if(count($route) == 2 && $route[0] == 'poll'){
                $poll = \App\Poll::findOrFail($route[1]);
                $meta = array(
                    'url' => $request->fullUrl(),
                    'title' => $poll->name,
                    'description' => $poll->description,
                    'image' => $poll->imageLink,
                    'type' => 'article'
                );
            } else {
                $meta = array(
                    'url' => $request->fullUrl(),
                    'title' => 'დააფიქსირე შენი ხმა',
                    'description' => 'შექმენით სანდო გამოკითხვა წამებში',
                    'image' => 'https://s3.eu-central-1.amazonaws.com/laravel-pollitic/photos/PollImage_SPjqW_1541721334.png'
                );
            }

            return view('crawler')->with('meta', $meta);
        }
        return $next($request);
    }
}
