<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Candidate;
use App\Vote;


class ApiController extends Controller
{
    //
    public function index(){
        return Candidate::all();
    }

    public function vote(Request $request){
        $number = $request->input('number');
        $candidateId = $request->input('candidateId');
        Candidate::findOrFail($candidateId);

        //check if phone # is valid
        if(!preg_match("/^[0-9]{9}$/", $number)) {
            $this->returnError('გთხოვთ შეიყვანოთ სწორი 9 ნიშნა ნომერი!');
        }

        //check if the number has been used before(compare hash to database hashes)
        if(Vote::where('status', 'verified')->where('number', bcrypt($number))->count() > 0){
            $this->returnError('ეს ნომერი ერთხელ უკვე გამოყენებული იქნა!');
        }

        $vote = new Vote;
        //create pin and store it in memory for the time being.
        //this value is only kept within this function and 
        //is stored in the database as a hash
        $pin = rand(10000, 99999);

        //number and pin hashing
        $vote->number = bcrypt($number);
        $vote->pin = bcrypt($pin);

        $vote->age = $request->input('age');
        $vote->gender = $request->input('gender');
        $vote->candidate_id = $candidateId;
        $vote->status = 'unverified';

        $this->sendMessage('+995' . $number, 'გამარჯობა! თქვენი Pollitic-ის ვერიფიკაციის კოდი არის: ' . $pin); 
        
        $vote->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => 'ვერიფიკაციისათვის გთხოვთ შეამოწმოთ ჩვენი გამოგზავნილი SMS მესიჯი',
                'link' => url('/api/vote/' . $vote->id . '/' . 'verify/')
            ]
        ]);
    }

    public function returnError($message){
        return response()->json([
            'status' => 'error',
            'error' => $message
        ]);
    }

    public function sendMessage($number, $message){
        return 0;
    }
}
