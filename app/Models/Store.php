<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Store extends Model
{
    use HasFactory, Notifiable;

    protected $table = 'stores';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'description',
        'url',
        'slug',
        'address',
        'rif_path',
        'constitutive_document_path',
        'owner_id',
        'verified',
        'logo',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($store) {
            if (empty($store->{$store->getKeyName()})) {
                $store->{$store->getKeyName()} = (string) Str::ulid();
            }
        });
    }

    public function url(): Attribute
    {
        return Attribute::make(
            get: fn () => 'https://'.$this->slug.'.mama-subscription.localhost',
        );
    }

    // Relación con los usuarios (owner_store, employees, customers)
    public function users()
    {
        return $this->belongsToMany(User::class, 'store_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    // Relación específica con los 'employees'
    public function employees()
    {
        return $this->users()->wherePivot('role', 'employee');
    }

    // Relación específica con los 'customers'
    public function customers()
    {
        return $this->users()->wherePivot('role', 'customer');
    }

    // Relación específica con los 'owner_store'
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->logo
            ? Storage::disk('stores')->url($this->logo)  // Obtener la URL pública del logo
            : asset('images/default-logo.png'),  // Si no hay logo, usar una imagen por defecto
        );
    }

    public function plans()
    {
        return $this->hasManyThrough(
            Plan::class,  // Modelo destino (Service)
            Address::class,  // Modelo intermedio (Address)
            'store_id',      // Clave foránea en Address hacia Store
            'id',            // Clave foránea en Service
            'id',            // Clave primaria en Store
            'id'             // Clave primaria en Address
        );
    }

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class, 'store_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'store_id');
    }

    /**
     * Obtiene la cuenta bancaria por defecto de la tienda.
     */
    public function defaultBankAccount(): ?BankAccount
    {
        return $this->hasOne(BankAccount::class, 'store_id')->where('default_account', 1)->first();
    }

    // Método para obtener el email del propietario de la tienda
    public function routeNotificationForMail($notification)
    {
        return $this->owner ? $this->owner->email : null;
    }
}
