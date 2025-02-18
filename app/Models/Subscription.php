<?php

namespace App\Models;

use App\Enums\SubscriptionStatusEnum;
use App\Support\MoneyFormatter;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Money\Money;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status',
        'trial_ends_at',
        'renews_at',
        'ends_at',
        'last_notification_at',
        'store_id',
        'user_id',
        'expires_at',
        'service_name',
        'service_description',
        'service_price_cents',
        'service_free_days',
        'service_grace_period',
        'service_id',
        'frequency_days',
        'stripe_subscription_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => SubscriptionStatusEnum::class,
        'trial_ends_at' => 'datetime',
        'renews_at' => 'datetime',
        'ends_at' => 'datetime',
        'last_notification_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Accessor for service_price_cents to return formatted price.
     */
    public function formattedServicePrice(): Attribute
    {
        return Attribute::make(
            get: fn() => number_format($this->service_price_cents / 100, 2) . ' USD'
        );
    }

    /**
     * Accessor for frequency_days to describe the frequency in text.
     */
    public function frequencyDescription(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->frequency_days
            ? "{$this->frequency_name} ({$this->frequency_days} días)"
            : null
        );
    }

    public function isActive(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === SubscriptionStatusEnum::Active
        );
    }

    public function isOnTrial(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === SubscriptionStatusEnum::OnTrial
        );
    }

    public function isPastDue(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === SubscriptionStatusEnum::PastDue
        );
    }

    public function isCancelled(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === SubscriptionStatusEnum::Cancelled
        );
    }

    public function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === SubscriptionStatusEnum::Expired
        );
    }

    public function nextRenewalDate(): Attribute
    {
        return Attribute::make(
            get: function () {
                $renewalDate = $this->status === SubscriptionStatusEnum::OnTrial ? $this->trial_ends_at : $this->renews_at;

                // Asumimos que la frecuencia de pago es mensual si no se indica lo contrario
                return $this->frequency_days
                    ? $renewalDate->addDays($this->frequency_days)
                    : $renewalDate->addMonth();
            }
        );
    }

    public function hasEnded(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->ends_at->greaterThan(now())
        );
    }

    public function wasNotifiedRecently(): Attribute
    {
        return Attribute::make(
            get: fn() => abs(now()->diffInHours($this->last_notification_at)) <= 48
        );
    }

    public function canBePaid(): Attribute
    {
        return Attribute::make(
            get: fn() => !$this->is_on_trial && !$this->is_expired && !$this->is_cancelled
        );
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'service_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getPrice(): Money
    {
        if ($this->service_price_cents) {
            return Money::USD($this->service_price_cents);
        }

        return Money::USD(0); // Precio predeterminado (por ejemplo, $0.00)
    }

    public function formattedPrice(): string
    {
        return MoneyFormatter::make($this->getPrice())->format();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'subscription_id');
    }

    public function allTransactions(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->payments->flatMap->transactions
        );
    }

    public function hasBsPayment(): bool
    {
        return $this->payments()->where('is_bs', true)->exists();
    }

}
