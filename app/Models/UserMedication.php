<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMedication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'rxcui',
        'drug_name',
        'base_names',
        'dose_form_group_names',
    ];

    protected function casts(): array
    {
        return [
            'base_names' => 'array',
            'dose_form_group_names' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
