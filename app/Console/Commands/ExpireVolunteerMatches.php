<?php

namespace App\Console\Commands;

use App\Services\MatchExpiryService;
use Illuminate\Console\Command;

class ExpireVolunteerMatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire volunteer matches that passed their 48-hour response window';

    /**
     * Execute the console command.
     */
    public function handle(MatchExpiryService $expiry): int
    {
        $count = $expiry->expireStale();

        $this->info("Expired {$count} match(es) past their response window.");

        return self::SUCCESS;
    }
}
