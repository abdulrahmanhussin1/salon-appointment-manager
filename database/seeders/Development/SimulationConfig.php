<?php

namespace Database\Seeders\Development;

use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;

class SimulationConfig
{
    public const DEFAULT_SEED = 20260925;
    public const ANCHOR_DATE = '2026-09-25';
    public const DEFAULT_START_DATE = '2025-10-01';
    public const DEFAULT_END_DATE = '2026-09-30';

    protected static ?self $instance = null;

    public int $seed;
    public Carbon $startDate;
    public Carbon $endDate;
    public Carbon $anchorDate;
    public FakerGenerator $faker;

    public function __construct(?string $start = null, ?string $end = null, ?int $seed = null)
    {
        $this->ensureSafeEnvironment();

        $this->seed = $seed ?? (int) env('SIMULATION_SEED', self::DEFAULT_SEED);
        $this->anchorDate = Carbon::parse(env('SIMULATION_ANCHOR_DATE', self::ANCHOR_DATE))->startOfDay();
        $this->startDate = Carbon::parse($start ?? env('SIMULATION_START_DATE', self::DEFAULT_START_DATE))->startOfDay();
        $this->endDate = Carbon::parse($end ?? env('SIMULATION_END_DATE', self::DEFAULT_END_DATE))->endOfDay();

        // Seed random generators for determinism
        mt_srand($this->seed);
        srand($this->seed);

        $this->faker = FakerFactory::create('ar_SA');
        $this->faker->seed($this->seed);
    }

    public static function instance(?string $start = null, ?string $end = null, ?int $seed = null): self
    {
        if (! self::$instance || $start !== null || $seed !== null) {
            self::$instance = new self($start, $end, $seed);
        }

        return self::$instance;
    }

    /**
     * Safety check to ensure simulation never runs in production or staging.
     */
    public function ensureSafeEnvironment(): void
    {
        if (app()->environment(['production', 'prod', 'staging'])) {
            throw new \RuntimeException('ABORTED: Development simulation cannot be executed in production or staging environment.');
        }
    }

    /**
     * Deterministic random integer between min and max inclusive.
     */
    public function randomInt(int $min, int $max): int
    {
        return mt_rand($min, $max);
    }

    /**
     * Deterministic random float with given decimal precision.
     */
    public function randomFloat(float $min, float $max, int $decimals = 2): float
    {
        $factor = pow(10, $decimals);
        $rand = mt_rand((int) ($min * $factor), (int) ($max * $factor));

        return round($rand / $factor, $decimals);
    }

    /**
     * Deterministic random element from an array.
     */
    public function randomElement(array $items): mixed
    {
        if (empty($items)) {
            return null;
        }

        $keys = array_keys($items);
        $key = $keys[mt_rand(0, count($keys) - 1)];

        return $items[$key];
    }

    /**
     * Weighted choice from an associative array of [value => weight].
     */
    public function weightedChoice(array $weightedItems): mixed
    {
        $totalWeight = array_sum($weightedItems);
        $rand = mt_rand(1, (int) $totalWeight);

        $cumulative = 0;
        foreach ($weightedItems as $value => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $value;
            }
        }

        return array_key_first($weightedItems);
    }

    /**
     * Get monthly seasonality multiplier (1.0 = baseline).
     */
    public function getMonthlyMultiplier(int $month): float
    {
        return match ($month) {
            1 => 0.85,  // January post-holiday slowdown
            2 => 0.90,  // February
            3 => 1.25,  // March pre-Ramadan & Mother's Day
            4 => 0.70,  // April Ramadan fasting slowdown
            5 => 1.60,  // May Eid al-Fitr & early wedding peak
            6 => 1.35,  // June summer peak
            7 => 0.90,  // July travel
            8 => 0.95,  // August
            9 => 1.20,  // September back-to-school refresh
            10 => 1.05, // October autumn baseline
            11 => 1.10, // November pre-holiday
            12 => 1.45, // December year-end holiday rush
            default => 1.00,
        };
    }

    /**
     * Get day-of-week traffic multiplier.
     */
    public function getDayOfWeekMultiplier(int $dayOfWeek): float
    {
        return match ($dayOfWeek) {
            Carbon::THURSDAY => 1.70, // Weekend eve rush
            Carbon::FRIDAY => 1.60,   // Friday prime salon day
            Carbon::SATURDAY => 1.40, // Saturday family & grooming
            Carbon::WEDNESDAY => 1.10,// Mid-week uptick
            Carbon::TUESDAY => 0.95,  // Normal baseline
            Carbon::MONDAY => 0.70,   // Quiet day
            Carbon::SUNDAY => 0.65,   // Quiet day
            default => 1.00,
        };
    }
}
