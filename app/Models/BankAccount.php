<?php

namespace App\Models;

use App\Enums\BankEnum;
use App\Enums\IdentityPrefixEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $table = 'bank_accounts';

    protected $fillable = [
        'user_id',
        'store_id',
        'bank_code',
        'phone_number',
        'identity_prefix',
        'identity_number',
        'default_account',
    ];

    protected $casts = [
        'identity_prefix' => IdentityPrefixEnum::class,
        'bank_code' => BankEnum::class,
    ];

    protected $appends = [
        'identity_document',
    ];

    public function identityDocument(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->identity_prefix->value}-{$this->identity_number}",
        );
    }

    /**
     * Relación: Una cuenta bancaria pertenece a un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: Una cuenta bancaria puede pertenecer a una tienda.
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
