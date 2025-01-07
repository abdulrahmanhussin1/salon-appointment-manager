<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerTransaction extends Model
{
    use HasFactory, HasUserActions;

    protected $guarded = ['id'];
    protected $table = 'customer_transactions';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public static function getAvailableDeposits($customerId)
    {
        return self::where('customer_id', $customerId)
            ->where('status', 'available')
            ->where('amount', '>', 0)
            ->orderBy('created_at');
    }

    public static function useDepositsForInvoice($customerId, $invoiceAmount)
    {
        return DB::transaction(function () use ($customerId, $invoiceAmount) {
            $availableDeposits = self::getAvailableDeposits($customerId)->lockForUpdate()->get();

            $remainingAmount = $invoiceAmount;
            $usedDeposits = [];

            foreach ($availableDeposits as $deposit) {
                if ($remainingAmount <= 0) break;

                if ($deposit->amount <= $remainingAmount) {
                    // Use entire deposit
                    $usedAmount = $deposit->amount;
                    $deposit->status = 'used';
                    $deposit->save();
                } else {
                    // Split deposit
                    $usedAmount = $remainingAmount;

                    // Update current deposit
                    $deposit->amount -= $usedAmount;
                    $deposit->save();
                }

                $usedDeposits[] = [
                    'amount' => $usedAmount,
                    'deposit_id' => $deposit->id
                ];
                $remainingAmount -= $usedAmount;
            }

            return [
                'used_deposits' => $usedDeposits,
                'remaining_to_pay' => max(0, $remainingAmount)
            ];
        });
    }

}

