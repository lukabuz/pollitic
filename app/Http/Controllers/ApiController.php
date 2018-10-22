<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Candidate;
use App\Vote;
use GuzzleHttp\Client as HTTPClient;
use GuzzleHttp\Psr7\Request as HTTPRequest;

class ApiController extends Controller
{
    //
    public function index(){
        $candidates = Candidate::all();
        $data = array();

        foreach($candidates as $candidate){
            $candidate->voteCount = $candidate->voteCount();
            array_push($data, $candidate);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function vote(Request $request){
        $number = $request->input('number');
    
        // if(!$this->verifyCaptcha($request)){
        //     return $this->returnError('გთხოვთ დაამტკიცოთ, რომ არ ხართ რობოტი');
        // }

        if(!$request->exists('candidateId') || $request->input('candidateId') == ''){
            return $this->returnError('გთხოვთ აირჩიოთ კანდიდატი!');
        }

        $candidateId = $request->input('candidateId');

        //check if phone # is valid
        $toMatch = '#^[+][1-9]{1}[0-9]{3,14}#';
        if(!preg_match($toMatch , $number)) {
            return $this->returnError('გთხოვთ შეიყვანოთ სწორი ნომერი!');
        }

        //check if the number has been used before(compare hash to database hashes)
        foreach(Vote::where('status', 'verified')->get() as $vote){
            if(Hash::check($number, $vote->number)){
                return $this->returnError('ეს ნომერი ერთხელ უკვე გამოყენებულია!');
            };
        }

        $vote = new Vote;
        //create pin and store it in memory for the time being.
        //this value is only kept within this function and 
        //is stored in the database as a hash
        $pin = rand(10000, 99999);

        //number and pin hashing
        $vote->number = Hash::make($number);
        $vote->pin = Hash::make($pin);

        $vote->age = $request->input('age');
        $vote->gender = $request->input('gender');
        $vote->candidate_id = $candidateId;
        $vote->status = 'unverified';

        $res = $this->sendMessage($number, 'გამარჯობა! თქვენი Pollitic-ის ვერიფიკაციის კოდი არის: ' . $pin);
        if(!$res){
            return $this->returnError('მესიჯის გაგზავნისას დაფიქსირდა შეცდომა.');
        }

        $vote->save();

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
        $client = new GuzzleHttp\Client(['base_uri' => 'https://cheapsms.slockz.com/']);

        $response = $client->request('GET', 'rest?act=sms&to=' . $number . '&msg=' . $message . '&token=' . env('SMS_TOKEN'));
        die($response);
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
