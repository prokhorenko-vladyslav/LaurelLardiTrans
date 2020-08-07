<?php

namespace Laurel\LardiTrans\App\Console\Commands;

use Illuminate\Console\Command;
use Laurel\LardiTrans\App\Services\LardiTransService;

class LardiFetchCountries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laurel/lardi-trans/fetch:countries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch all countries from the LardiTrans api';

    /**
     * Instance of the LardiTrans service
     *
     * @var LardiTransService
     */
    protected $lardiTransService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(LardiTransService $lardiTransService)
    {
        $this->lardiTransService = $lardiTransService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->line("Countries fetching has been started...");
        $countries = $this->lardiTransService->fetchCountries();
        $this->info("Fetching has been ended. {$countries->count()} countries has been loaded and updated. Time: " . (microtime(true) - LARAVEL_START));
    }
}
