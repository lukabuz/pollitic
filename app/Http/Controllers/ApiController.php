<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Candidate;
use App\Vote;
use Twilio\Rest\Client;

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
        $candidateId = $request->input('candidateId');
        Candidate::findOrFail($candidateId);

        //check if phone # is valid
        if(!preg_match("/^+[0-9]{12}$/", $number)) {
            return $this->returnError('გთხოვთ შეიყვანოთ სწორი 9 ნიშნა ნომერი!');
        }

        //check if the number has been used before(compare hash to database hashes)
        foreach(Vote::where('status', 'verified')->get() as $vote){
            if(Hash::check($number, $vote->number)){
                return $this->returnError('ეს ნომერი ერთხელ უკვე გამოყენებული იქნა!');
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

        $this->sendMessage($number, 'გამარჯობა! თქვენი Pollitic-ის ვერიფიკაციის კოდი არის: ' . $pin); 
        
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
        } else { $this->returnError('შეყვანილი ვერიფიკაციის კოდი არასწორია!'); }
    }

    public function returnError($message){
        return response()->json([
            'status' => 'error',
            'error' => $message
        ]);
    }

    public function sendMessage($number, $message){
        $client = new Client(getenv('TWILIO_SID'), getenv('TWILIO_TOKEN'));
        
        $client->messages->create(
            // the number you'd like to send the message to
            $number,
            array(
                // A Twilio phone number you purchased at twilio.com/console
                'from' => getenv('TWILIO_FROM'),
                // the body of the text message you'd like to send
                'body' => $message
            )
        );

        return 0;
    }
}
