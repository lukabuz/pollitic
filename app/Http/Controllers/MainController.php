<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Poll;
use App\Candidate;

class MainController extends Controller
{
    //
    public function index(){
        $polls = Poll::where('isListed', 'True')->get();

        $data = array();

        foreach($polls as $poll){
            $poll->totalVotes = $poll->totalVotes();
            array_push($data, $poll);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'polls' => $polls
            ]
        ]);
    }

    public function createPoll(Request $request){
        //check if all required fields are given
        if(!$request->exists('name') || $request->input('name') == '') { $error = 'გთხოვთ შეიყვანოთ გამოკითხვის სათაური'; }
        if(!$request->exists('description') || $request->input('description') == '') { $error = 'გთხოვთ შეიყვანოთ გამოკითხვის აღწერა'; }
        if(!$request->exists('charts') || $request->input('charts') == '') { $error = 'გთხოვთ მიუთითოთ რეზულტატების გამოსახვის მეთოდი'; }
        if(!$request->exists('requirePhoneAuth') || $request->input('requirePhoneAuth') == '') { $error = 'გთხოვთ მიუთითოთ გსურთ თუ არა ხმის მიცემისას მობილური ვერიფიკაციის გამოყენება'; }
        if(!$request->exists('isListed') || $request->input('isListed') == '') { $error = 'გთხოვთ მიუთითოთ გსურთ თუ არა გამოკითხვის გასაჯაროება(საიტზე ნებისმიერი შემომსვლელისათვის მისი გამოჩენა)'; }
        if(!$request->exists('candidates') || $request->input('candidates') == '') { $error = 'გთხოვთ მიუთითოთ მინიმუმ 1 არჩევანი'; }

        if(isset($error)) { return $this->returnError($error); }
        

        $poll = new Poll;

        $poll->name = $request->input('name');
        $poll->description = $request->input('description');
        $poll->charts = $request->input('charts');
        $poll->cookieValue = '';

        if($request->input('requirePhoneAuth') == 'True'){
            $poll->requirePhoneAuth = 'True';
        } else {
            $poll->requirePhoneAuth = 'False';
        }
        
        if($request->input('isListed') == 'True'){
            $poll->isListed = 'True';
        } else {
            $poll->isListed = 'False';
        }
        
        if($request->exists('password') && $request->input('password') !== ''){
            $poll->password = Hash::make($request->input('password'));
        }

        if($request->exists('imageLink') && $request->input('imageLink') !== ''){
            $poll->imageLink = $request->input('imageLink');
        }

        $poll->save();

        foreach($request->candidates as $candidate){
            $newCandidate = new Candidate;
            $newCandidate->name = $candidate;
            $newCandidate->poll_id = $poll->id;
            $newCandidate->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'თქვენი გამოკითხვა წარმატებით შეიქმნა!',
            'data' => [
                'poll' => $poll
            ]
        ]);
    }

    public function returnError($message){
        return response()->json([
            'status' => 'error',
            'error' => $message
        ]);
    }
}
