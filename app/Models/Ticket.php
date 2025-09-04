<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    /** @var array<string> Attributes that are mass assignable */
    protected $fillable = [
        'subject',    // Ticket subject line
        'body',       // Main ticket content
        'status',     // Current status (open, pending, closed)
        'category',   // AI-classified or manually set category
        'note',       // Optional internal notes
        'explanation', // AI classification explanation
        'confidence', // AI classification confidence score
    ];

    /** @var array<string, string> Cast attributes to native types */
    protected $casts = [
        'confidence' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     * Automatically generates a ULID for new tickets.
     */
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }
}
