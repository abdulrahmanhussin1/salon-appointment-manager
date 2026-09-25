<?php

namespace App\Console\Commands;

use Database\Seeders\DevelopmentSimulationSeeder;
use Illuminate\Console\Command;

class SeedSimulationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-simulation
                            {--start= : Start date of the simulation window (YYYY-MM-DD)}
                            {--end= : End date of the simulation window (YYYY-MM-DD)}
                            {--seed= : Deterministic integer seed for reproducible random behavior}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed a realistic, deterministic 1-year operating business simulation for development and testing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (app()->environment(['production', 'prod', 'staging'])) {
            $this->error('ABORTED: This command is restricted to development and testing environments only.');

            return self::FAILURE;
        }

        $start = $this->option('start');
        $end = $this->option('end');
        $seed = $this->option('seed') ? (int) $this->option('seed') : null;

        $seeder = new DevelopmentSimulationSeeder();
        $seeder->setCommand($this);
        $seeder->run($start, $end, $seed);

        return self::SUCCESS;
    }
}
