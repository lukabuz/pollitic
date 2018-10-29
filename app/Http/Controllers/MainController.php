<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Poll;
use App\PollQuestionAnswer;
use App\PollQuestion;
use App\Candidate;
use App\Vote;

class MainController extends Controller
{
    //
    public function ongoing(Request $request)
    {
        $number = $request->input('number', 100000000);

        $polls = Poll::where('isListed', 'True')->where('isClosed', 'False')->take($number)->get();

        $sorting = $request->input('sort', 'hot');
        
        if ($sorting == 'new') {
            $polls = $polls->sortByDesc(function ($poll) {
                return $poll->id;
            });
        } else {
            $polls = $polls->sortByDesc(function ($poll, $key) {
                return Vote::where('poll_id', $poll->id)->count();
            });
        }

        $data = array();

        foreach ($polls as $poll) {
            $poll->totalVotes = $poll->totalVotes();
            array_push($data, $poll);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'polls' => $data,
            ]
        ]);
    }

    public function closed(Request $request)
    {
        $number = $request->input('number', 100000000);

        $polls = Poll::where('isListed', 'True')->where('isClosed', 'True')->take($number)->get();

        $sorting = $request->input('sort', 'hot');
        
        if ($sorting == 'new') {
            $polls = $polls->sortByDesc(function ($poll) {
                return $poll->id;
            });
        } else {
            $polls = $polls->sortByDesc(function ($poll, $key) {
                return Vote::where('poll_id', $poll->id)->count();
            });
        }

        $data = array();

        foreach ($polls as $poll) {
            $poll->totalVotes = $poll->totalVotes();
            array_push($data, $poll);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'polls' => $data,
            ]
        ]);
    }

    public function createPoll(Request $request)
    {
        //check if all required fields are given
        if (!$request->exists('name') || $request->input('name') == '') {
            $error = 'გთხოვთ შეიყვანოთ გამოკითხვის სათაური';
        }
        if (!$request->exists('description') || $request->input('description') == '') {
            $error = 'გთხოვთ შეიყვანოთ გამოკითხვის აღწერა';
        }
        // if(!$request->exists('charts') || $request->input('charts') == '') { $error = 'გთხოვთ მიუთითოთ რეზულტატების გამოსახვის მეთოდი'; }
        if (!$request->exists('requirePhoneAuth') || $request->input('requirePhoneAuth') == '') {
            $error = 'გთხოვთ მიუთითოთ გსურთ თუ არა ხმის მიცემისას მობილური ვერიფიკაციის გამოყენება';
        }
        if (!$request->exists('isListed') || $request->input('isListed') == '') {
            $error = 'გთხოვთ მიუთითოთ გსურთ თუ არა გამოკითხვის გასაჯაროება(საიტზე ნებისმიერი შემომსვლელისათვის მისი გამოჩენა)';
        }
        if (!$request->exists('candidates') || $request->input('candidates') == '') {
            $error = 'გთხოვთ მიუთითოთ მინიმუმ 1 არჩევანი';
        }
        if (!$request->exists('closingDate') || $request->input('closingDate') == '') {
            $error = 'გთხოვთ მიუთითოთ გამოკითხვის დამთავრების თარიღი.';
        }

        if ($request->exists('questions') && count($request->questions) > 5) {
            $error = 'გთხოვთ დასვათ მაქსიმუმ 5 დამატებითი კითხვა.';
        }

        try {
            $closingDate = Carbon::parse($request->input('closingDate'));
        } catch (\Exception $er) {
            $error = 'გთხოვთ შეიყვანოთ სწორი თარიღის ფორამატი';
        }

        if ($request->hasFile('image')) {
            if ($request->File('image')->getClientSize() > 4000000) {
                $error = 'სურათის ზომა არ უნდა აღემატებოდეს 4 მეგაბაიტს.';
            }
            $fileNameToStore = 'PollImage_'. str_random(5) . '_' . time() . '.' . $request->file('image')->getClientOriginalExtension();
            $request->file('image')->storeAs('photos', $fileNameToStore, 's3', 'public');
            $fileNameToStore = 'https://s3.' . env('AWS_DEFAULT_REGION') . '.amazonaws.com/laravel-pollitic/photos/' . $fileNameToStore;
        }

        if (isset($error)) {
            return $this->returnError($error);
        }
        
        $poll = new Poll;

        $poll->name = $request->input('name');
        $poll->description = $request->input('description');
        $poll->charts = '';
        $poll->cookieValue = '';
        if ($fileNameToStore) {
            $poll->imageLink = $fileNameToStore;
        }

        $poll->closingDate = $closingDate;

        if ($request->input('requirePhoneAuth') == 'True') {
            $poll->requirePhoneAuth = 'True';
        } else {
            $poll->requirePhoneAuth = 'False';
        }
        
        if ($request->input('isListed') == 'True') {
            $poll->isListed = 'True';
        } else {
            $poll->isListed = 'False';
        }
        
        if ($request->exists('password') && $request->input('password') !== '') {
            $poll->password = Hash::make($request->input('password'));
        }

        $poll->save();

        foreach ($request->candidates as $candidate) {
            $newCandidate = new Candidate;
            $newCandidate->name = $candidate;
            $newCandidate->poll_id = $poll->id;
            $newCandidate->save();
        }

        if ($request->exists('questions')) {
            foreach ($request->questions as $question) {
                $newQuestion = new PollQuestion;
                $newQuestion->question = $question;
                $newQuestion->poll_id = $poll->id;
                $newQuestion->save();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'თქვენი გამოკითხვა წარმატებით შეიქმნა!',
            'data' => [
                'poll' => $poll
            ]
        ]);
    }

    public function returnError($message)
    {
        return response()->json([
            'status' => 'error',
            'error' => $message
        ]);
    }
}
