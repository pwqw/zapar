<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConsentLog extends Model
{
    protected $fillable = [
        'user_id',
        'consent_type',
        'version',
        'ip_address',
        'user_agent',
        'accepted',
    ];

    protected function casts(): array
    {
        return [
            'accepted' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
