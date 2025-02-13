<?php

namespace App\Models;

use App\DTO\MiBancoMetadata;
use App\DTO\StripeMetadata;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'from_type',
        'from_id',
        'to_type',
        'to_id',
        'type',
        'status',
        'date',
        'amount_cents',
        'metadata',
        'payment_id',
        'stripe_payment_id', // Nuevo campo agregado
        'stripe_invoice_id',
        'is_bs',
    ];

    protected $casts = [
        'to_id' => 'string',
        'type' => TransactionTypeEnum::class,
        'status' => TransactionStatusEnum::class,
        'date' => 'date',
        'metadata' => 'array',
        'is_bs' => 'boolean',
    ];

    public function from(): MorphTo
    {
        return $this->morphTo();
    }

    public function to(): MorphTo
    {
        return $this->morphTo();
    }

    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }

    /**
     * Relación con Payment.
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Relación con Store si el to_type es Store.
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'to_id');
    }

    /**
     * Crear una transacción desde un Payment Intent.
     *
     * @param  \Stripe\PaymentIntent  $paymentIntent
     * @return static
     */
    public static function createFromPaymentIntent($paymentIntent, Payment $payment): self
    {
        return self::create([
            'from_type' => get_class($payment->subscription->user),
            'from_id' => $payment->subscription->user->id,
            'to_type' => get_class($payment->subscription->service->store),
            'to_id' => $payment->subscription->service->store->id,
            'type' => TransactionTypeEnum::Subscription->value,
            'status' => self::mapStripeStatusToLocal($paymentIntent->status),
            'date' => now(),
            'amount_cents' => $paymentIntent->amount,
            'metadata' => $paymentIntent->toArray(),
            'payment_id' => $payment->id,
            'stripe_payment_id' => $paymentIntent->id,
        ]);
    }

    /**
     * Mapear el estado de Stripe a un estado local.
     *
     * @return string
     */
    protected static function mapStripeStatusToLocal(string $stripeStatus): TransactionStatusEnum
    {
        return match ($stripeStatus) {
            'requires_payment_method' => TransactionStatusEnum::RequiresPaymentMethod,
            'requires_confirmation' => TransactionStatusEnum::RequiresConfirmation,
            'requires_action' => TransactionStatusEnum::RequiresAction,
            'processing' => TransactionStatusEnum::Processing,
            'succeeded' => TransactionStatusEnum::Succeeded,
            'canceled' => TransactionStatusEnum::Canceled,
            'failed' => TransactionStatusEnum::Failed,
            default => TransactionStatusEnum::Failed, // O un valor predeterminado que prefieras
        };
    }

    /**
     * Convierte la metadata en un objeto específico basado en el proveedor.
     *
     * @return StripeMetadata|MiBancoMetadata|null
     */
    public function getMetadataAsObject()
    {
        if (!isset($this->metadata) || !is_array($this->metadata)) {
            return null;
        }

        if (array_key_exists('object', $this->metadata) && $this->metadata['object'] === 'payment_intent') {
            // Es metadata de Stripe
            return new StripeMetadata($this->metadata);
        }

        if (array_key_exists('code', $this->metadata)) {
            // Es metadata de MiBanco
            return new MiBancoMetadata($this->metadata);
        }

        return null; // No se puede identificar el tipo de metadata
    }
}
