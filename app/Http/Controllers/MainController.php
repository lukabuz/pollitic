<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as HTTPClient;
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
        $number = $request->input('number', false);

        $polls = Poll::where('isListed', 'True')->where('isClosed', 'False')->get();

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

        if($number){
            $polls = $polls->take($number);
        }

        $data = array();

        foreach ($polls as $poll) {
            $poll->totalVotes = $poll->totalVotes();
            array_push($data, $poll);
        }

        $paginatedData = $this->paginate($data, (int)$request->input('perPage', 10), (int)$request->input('page', 1));

        return response()->json([
            'status' => 'success',
            'data' => [
                'polls' => $paginatedData['data'],
                'page' => $paginatedData['page'],
                'totalPages' => $paginatedData['totalPages']
            ]
        ]);
    }

    public function closed(Request $request)
    {
        $number = $request->input('number', false);

        $polls = Poll::where('isListed', 'True')->where('isClosed', 'True')->get();

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

        if($number){
            $polls = $polls->take($number);
        }

        $data = array();

        foreach ($polls as $poll) {
            $poll->totalVotes = $poll->totalVotes();
            array_push($data, $poll);
        }

        $paginatedData = $this->paginate($data, (int)$request->input('perPage', 10), (int)$request->input('page', 1));

        return response()->json([
            'status' => 'success',
            'data' => [
                'polls' => $paginatedData['data'],
                'page' => $paginatedData['page'],
                'totalPages' => $paginatedData['totalPages']
            ]
        ]);
    }

    public function createPoll(Request $request)
    {
        //check if all required fields are given
        if(!$this->verifyCaptcha($request)){
            $error = 'გთხოვთ დაამტკიცოთ, რომ არ ხართ რობოტი';
            $errorVariable = 'recaptcha';
        }

        if (!$request->exists('requirePhoneAuth') || $request->input('requirePhoneAuth') == '') {
            $error = 'გთხოვთ მიუთითოთ გსურთ თუ არა ხმის მიცემისას მობილური ვერიფიკაციის გამოყენება';
            $errorVariable = 'requirePhoneAuth';
        }
        if (!$request->exists('isListed') || $request->input('isListed') == '') {
            $error = 'გთხოვთ მიუთითოთ გსურთ თუ არა გამოკითხვის გასაჯაროება(საიტზე ნებისმიერი შემომსვლელისათვის მისი გამოჩენა)';
            $errorVariable = 'isListed';
        }
        if (!$request->exists('closingDate') || $request->input('closingDate') == '') {
            $error = 'გთხოვთ მიუთითოთ გამოკითხვის დამთავრების თარიღი.';
            $errorVariable = 'closingDate';
        }

        // if ($request->exists('questions') && request('questions') != null && count($request->questions) > 5) {
        //     $error = 'გთხოვთ დასვათ მაქსიმუმ 5 დამატებითი კითხვა.';
        //     $errorVariable = 'questions';
        // }

        try {
            $closingDate = Carbon::createFromTimestamp($request->input('closingDate'))->toDateTimeString();
        } catch (\Exception $er) {
            $error = 'გთხოვთ შეიყვანოთ სწორი თარიღის ფორამატი';
            $errorVariable = 'closingDate';
        }

        if ($request->hasFile('image')) {
            $extensions = ['jpg', 'jpeg', 'png'];
            if ($request->File('image')->getClientSize() > 4000000) {
                $error = 'სურათის ზომა არ უნდა აღემატებოდეს 4 მეგაბაიტს.';
                $errorVariable = 'image';
            } elseif (!in_array($request->File('image')->getClientOriginalExtension() , $extensions)) {
                $error = 'სურათი უნდა იყოს ან jpg, jpeg, ან png ფორმატი.';
                $errorVariable = 'image';
            } else {
                $fileNameToStore = 'PollImage_'. str_random(5) . '_' . time() . '.' . $request->file('image')->getClientOriginalExtension();
                $request->file('image')->storeAs('photos', $fileNameToStore, 's3', 'public');
                $fileNameToStore = 'https://s3.' . env('AWS_DEFAULT_REGION') . '.amazonaws.com/laravel-pollitic/photos/' . $fileNameToStore;
            }
            
        }

        if (!$request->exists('candidates') || $request->input('candidates') == '' || !is_array($request->input('candidates'))) {
            $error = 'გთხოვთ მიუთითოთ მინიმუმ 1 არჩევანი';
            $errorVariable = 'candidates';
        } else {
            foreach($request->input('candidates') as $candidate){
                if(mb_strlen($candidate) > 30 || mb_strlen($candidate) < 2){
                    $error = 'თითო პასუხი უნდა იყოს მინიმუმ 2 და მაქსიმუმ 30 ასო';
                    $errorVariable = 'candidates';
                }
            }
        }

        if ($request->exists('description') && $request->input('description') == '') {
            $error = 'გთხოვთ შეიყვანოთ გამოკითხვის აღწერა';
            $errorVariable = 'description';
        } elseif (mb_strlen($request->input('description')) > 350 || mb_strlen($request->input('description')) < 10) {
            $error = 'გამოკითხვის აღწერა უნდა იყოს მინიმუმ 10 და მაქსიმუმ 350 ასო';
            $errorVariable = 'description';
        }

        if (!$request->exists('name') || $request->input('name') == '') {
            $error = 'გთხოვთ შეიყვანოთ გამოკითხვის სათაური';
            $errorVariable = 'name';
        } elseif (mb_strlen($request->input('name')) > 80 || mb_strlen($request->input('name')) < 5) {
            $error = 'გამოკითხვის სათაური უნდა იყოს მინიმუმ 5 და მაქსიმუმ 80 ასო';
            $errorVariable = 'name';
        }

        if (isset($error)) {
            return $this->returnError($error, $errorVariable);
        }
        
        $poll = new Poll;

        $poll->name = $request->input('name');
        $poll->description = $request->input('description');
        $poll->charts = '';
        $poll->cookieValue = '';
        if (isset($fileNameToStore)) {
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

        $poll->save();

        foreach ($request->candidates as $candidate) {
            $newCandidate = new Candidate;
            $newCandidate->name = $candidate;
            $newCandidate->poll_id = $poll->id;
            $newCandidate->save();
        }

        // if ($request->exists('questions')) {
        //     foreach ($request->questions as $question) {
        //         $newQuestion = new PollQuestion;
        //         $newQuestion->question = $question;
        //         $newQuestion->poll_id = $poll->id;
        //         $newQuestion->save();
        //     }
        // }

        return response()->json([
            'status' => 'success',
            'message' => 'თქვენი გამოკითხვა წარმატებით შეიქმნა!',
            'data' => [
                'poll' => $poll
            ]
        ]);
    }

    public function returnError($message, $field = false)
    {
        if(!$field){
            $json = [
                'status' => 'error',
                'error' => $message
            ];
        } else {
            $json = [
                'status' => 'error',
                'error' => $message,
                'field' => $field
            ];
        }        
        
        return response()->json($json);
    }

    public function paginate($data, $perPage, $page){
        $length = count($data);
        $totalPages = ceil( $length/ $perPage );
        
        $page = max($page, 1);
        $page = min($page, $totalPages);
        
        $offset = ($page - 1) * $perPage;
        if( $offset < 0 ) $offset = 0;
        
        $paginatedData = array_slice( $data, $offset, $perPage );
        
        return array('data' => $paginatedData, 'page' => $page, 'totalPages' => $totalPages);
    }

    public function verifyCaptcha($request){
        $client = new HTTPClient();

    	$ip = explode(",", $request->header('x-forwarded-for'));
        $ip = $ip[0];
    
        $response = $client->post(
            'https://www.google.com/recaptcha/api/siteverify',
            ['form_params'=>
                [
                    'secret'=> env('GOOGLE_RECAPTCHA_SECRET'),
                    'response'=> $request->input('recaptcha'),
                    'remoteip'=> $ip
                ]
            ]
        );

        $body = json_decode((string)$response->getBody());
        
        return $body->success;
    }
}
