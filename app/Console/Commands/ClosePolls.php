<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Poll;

class ClosePolls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'polls:close';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets the isClosed value for closed polls to true';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $polls = Poll::all();

        foreach($polls as $poll){
            if(Carbon::now()->gt(Carbon::parse($poll->closingDate))){
                $poll->isClosed = 'True';
                $poll->save();
            }
        }
    }
}
