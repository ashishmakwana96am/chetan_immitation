<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use LogsActivity;

    const CATEGORIES = [
        'Rent',
        'Salary',
        'Utility',
        'Transport',
        'Maintenance',
        'Marketing',
        'Other',
    ];

    const PAYMENT_METHODS = [
        'Cash',
        'Bank Transfer',
        'UPI',
        'Card',
    ];

    protected $fillable = [
        'title',
        'category',
        'amount',
        'expense_date',
        'payment_method',
        'location_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
