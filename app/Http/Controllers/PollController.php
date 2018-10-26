<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Candidate;
use App\Vote;
use App\Poll;
use App\PollQuestionAnswer;
use App\PollQuestion;
use GuzzleHttp\Client as HTTPClient;
use GuzzleHttp\Psr7\Request as HTTPRequest;

class PollController extends Controller
{
    //
    public function index($id){
        $poll = Poll::findOrFail($id);

        $poll->candidates;
        $poll->questions;

        return response()->json([
            'status' => 'success',
            'data' => [
                'poll' => $poll,
            ]
        ]);
    }

    public function vote(Request $request, $id){
        return $request;
        $poll = Poll::findOrFail($id);

        if($poll->password !== null){
            if(!Hash::check($request->input('password'), $poll->password)){
                return $this->returnError('შეყვანილი პაროლი არასწორია!');
            }
        }
        
        $number = $request->input('number');
    
        // if(!$this->verifyCaptcha($request)){
        //     return $this->returnError('გთხოვთ დაამტკიცოთ, რომ არ ხართ რობოტი');
        // }

        if(!$request->exists('candidateId') || $request->input('candidateId') == ''){
            return $this->returnError('გთხოვთ აირჩიოთ კანდიდატი!');
        }

        $candidateId = $request->input('candidateId');

        if(Candidate::where('poll_id', $poll->id)->where('id', $candidateId)->count() == 0){
            return $this->returnError('გთხოვთ აირჩიოთ ამ გამოკითხვის შესაბამისი კანდიდატი!');
        }

        if($poll->requirePhoneAuth == 'True'){
            //check if phone # is valid
            $toMatch = '#^[+][1-9]{1}[0-9]{3,14}#';
            if(!preg_match($toMatch , $number)) {
                return $this->returnError('გთხოვთ შეიყვანოთ სწორი ნომერი!');
            }
            
            //check if the number has been used before(compare hash to database hashes)
            foreach(Vote::where('status', 'verified')->where('poll_id', $poll->id)->get() as $vote){
                if(Hash::check($number, $vote->number)){
                    return $this->returnError('ეს ნომერი ერთხელ უკვე გამოყენებულია!');
                };
            }
        }

        $vote = new Vote;
        //create pin and store it in memory for the time being.
        //this value is only kept within this function and 
        //is stored in the database as a hash
        $pin = rand(10000, 99999);

        $vote->age = $request->input('age');
        $vote->gender = $request->input('gender');
        $vote->candidate_id = $candidateId;
        $vote->status = 'unverified';

        //number and pin hashing
        if($poll->requirePhoneAuth == 'False'){
            $vote->number = '';
            $vote->pin = '';
        } else {
            $vote->number = Hash::make($number);
            $vote->pin = Hash::make($pin);

            $res = $this->sendMessage($number, 'გამარჯობა! თქვენი Pollitic-ის ვერიფიკაციის კოდი არის: ' . $pin);
            if(!$res){
                return $this->returnError('მესიჯის გაგზავნისას დაფიქსირდა შეცდომა.');
            }
        }

        $res = $this->sendMessage($number, 'გამარჯობა! თქვენი Pollitic-ის ვერიფიკაციის კოდი არის: ' . $pin);
        if(!$res){
            return $this->returnError('მესიჯის გაგზავნისას დაფიქსირდა შეცდომა.');
        }

        $vote->poll_id = $poll->id;

        $vote->save();

        foreach($request->questions as $question){
            $answer = new PollQuestionAnswer;
            $answer->vote_id = $vote['id'];
            $answer->poll_question_id = $question['id'];
            $answer->answer = $question['answer'];
            $answer->save();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => 'ვერიფიკაციისათვის გთხოვთ შეამოწმოთ ჩვენი გამოგზავნილი SMS მესიჯი',
                'link' => url('/api/vote/' . $vote->id . '/' . 'verify/')
            ]
        ]);
    }

    public function verify(Request $request, $id){
        $vote = Vote::findOrFail($id);

        if(Hash::check($request->input('pin'), $vote->pin)){
            $vote->status = 'verified';
            $vote->save();
            return response()->json([
                'status' => 'success',
                'data' => [
                    'message' => 'ვერიფიკაციამ წარმატებით ჩაიარა! თქვენი ხმა მიღებულია.',
                ]
            ]);
        }

        return $this->returnError('შეყვანილი ვერიფიკაციის კოდი არასწორია!'); 
    }

    public function returnError($message){
        return response()->json([
            'status' => 'error',
            'error' => $message
        ]);
    }

    public function sendMessage($number, $message){
        $client = new HTTPClient(['base_uri' => 'https://cheapsms.slockz.com/']);

        $response = $client->request('GET', 'rest?act=sms&to=' . $number . '&msg=' . $message . '&token=' . env('SMS_TOKEN'));

        return $response->getStatusCode() == 200;
    }

    public function verifyCaptcha($request){
        $client = new HTTPClient();

        $ip = $request->header('x-forwarded-for');

    	$ip = explode(",",$ip);

        $ip = $ip[0];
    
        $response = $client->post(
            'https://www.google.com/recaptcha/api/siteverify',
            ['form_params'=>
                [
                    'secret'=> env('GOOGLE_RECAPTCHA_SECRET'),
                    'response'=> $request->input('value'),
                    'remoteip'=> $ip
                ]
            ]
        );

        $body = json_decode((string)$response->getBody());
        
        return $body->success;
    }
}
