<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
        foreach($poll->candidates as $candidate){
            $candidate->voteCount = $candidate->voteCount();
        }
        $poll->totalVotes = $poll->totalVotes();

        return response()->json([
            'status' => 'success',
            'data' => [
                'poll' => $poll,
            ]
        ]);
    }

    public function vote(Request $request, $id){
        $poll = Poll::findOrFail($id);

        if($poll->isClosed == 'True'){
            return $this->returnError('გამოკითხვა დასრულებულია!');
        }

        if ($poll->password !== null) {
            if (!Hash::check($request->input('password'), $poll->password)) {
                return $this->returnError('შეყვანილი პაროლი არასწორია!', 'password');
            }
        }
    
        if(!$this->verifyCaptcha($request)){
            return $this->returnError('გთხოვთ დაამტკიცოთ, რომ არ ხართ რობოტი', 'recaptcha');
        }

        if (!$request->exists('candidateId') || $request->input('candidateId') == '') {
            return $this->returnError('გთხოვთ აირჩიოთ კანდიდატი!', 'candidateId');
        }

        $candidateId = $request->input('candidateId');

        if (Candidate::where('poll_id', $poll->id)->where('id', $candidateId)->count() == 0) {
            return $this->returnError('გთხოვთ აირჩიოთ ამ გამოკითხვის შესაბამისი კანდიდატი!');
        }

        if ($poll->requirePhoneAuth == 'True') {
            $number = $request->input('number');
            //check if phone # is valid
            $toMatch = '#^[0-9]{3,14}#';
            if (!preg_match($toMatch, $number)) {
                return $this->returnError('გთხოვთ შეიყვანოთ სწორი ნომერი!', 'number');
            }
            
            //check if the number has been used before(compare hash to database hashes)
            foreach (Vote::where('status', 'verified')->where('poll_id', $poll->id)->get() as $vote) {
                if (Hash::check($number, $vote->number)) {
                    return $this->returnError('ეს ნომერი ერთხელ უკვე გამოყენებულია!');
                };
            }
        } else {
            $userAgent = $request->header('User-Agent');
            $ip = explode(",", $request->header('x-forwarded-for'));
            $uniqueID = md5($userAgent . $ip[0]);
            //check if the number has been used before(compare hash to database hashes)
            foreach (Vote::where('status', 'verified')->where('poll_id', $poll->id)->get() as $vote) {
                if ($vote->number == $uniqueID) {
                    return $this->returnError('თქვენგან ხმა უკვე დაფიქსირებულია!');
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

        //number and pin hashing
        if ($poll->requirePhoneAuth == 'False') {
            $vote->number = $uniqueID;
            $vote->status = 'verified';
            $vote->pin = '';
        } else {
            $vote->number = Hash::make($number);
            $vote->pin = Hash::make($pin);
            $vote->status = 'unverified';
            $res = $this->sendMessage($number, 'გამარჯობა! თქვენი Pollitic-ის ვერიფიკაციის კოდი არის: ' . $pin);
            Log::info('Sending SMS to ' . $number);
            if (!$res) {
                Log::info('Sending SMS to ' . $number . ' failed');
                return $this->returnError('მესიჯის გაგზავნისას დაფიქსირდა შეცდომა.');
            }
            Log::info('Sending SMS to ' . $number . ' success');
        }

        $vote->poll_id = $poll->id;

        $vote->save();

        if ($request->exists('questions')) {
            foreach ($request->questions as $question) {
                $answer = new PollQuestionAnswer;
                $answer->vote_id = $vote['id'];
                $answer->poll_question_id = $question['id'];
                $answer->answer = $question['answer'];
                $answer->save();
            }
        }

        if($poll->requirePhoneAuth == 'True'){
            return response()->json([
                'status' => 'success',
                'data' => [
                    'message' => 'ვერიფიკაციისათვის გთხოვთ შეამოწმოთ ჩვენი გამოგზავნილი SMS მესიჯი',
                    'link' => url('/api/vote/' . $vote->id . '/' . 'verify/')
                ]
            ]);
        } else {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'message' => 'თქვენი ხმა წარმატებით დაემატა!'
                ]
            ]);
        }        
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

        return $this->returnError('შეყვანილი ვერიფიკაციის კოდი არასწორია!', 'pin'); 
    }

    public function returnError($message, $field = false){
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

    public function sendMessage($number, $message){
        $url = 'https://sender.ge/api/send.php';

        //build params array
        $params = array(
            'apikey' => env('SMS_TOKEN'),
            'smsno' => 1,
            'destination' => $number,
            'content' => $message
        );

        //Build query using params
        //Most values can be just be put into the url string, but the 'content' param
        //is a message containing spaces, so this is a safer way of doing it.
        $query = http_build_query($params);
        
        Log::info($url . '?' . $query);        
        
        //set up fixie to communicate with sender.ge through a static IP
        $fixieUrl = getenv("FIXIE_URL");
        $parsedFixieUrl = parse_url($fixieUrl);

        $proxy = $parsedFixieUrl['host'].":".$parsedFixieUrl['port'];
        $proxyAuth = $parsedFixieUrl['user'].":".$parsedFixieUrl['pass'];

        //init curl and send request
        $ch = curl_init($url . '?' . $query);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyAuth);

        $result = curl_exec($ch);
        curl_close($ch);

        if($result){ Log::info('SMS GET result: ' . $result); }

        return $result;
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

    public function proxyRequest($url) {
        

        die($result);
    }
}
