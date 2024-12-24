<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\PaymentStatusEnum;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'stripe_invoice_id', // ID de la Invoice de Stripe
        'subscription_id',
        'status',
        'amount_cents',
        'due_date',
        'paid_date',
    ];

    protected $casts = [
        'status' => PaymentStatusEnum::class,
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    /**
     * Relación con la suscripción.
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Relación con las transacciones.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Verificar si el pago está vencido.
     */
    public function isOverdue(): bool
    {
        return $this->due_date < now() && is_null($this->paid_date);
    }

    /**
     * Marcar el pago como completado.
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => PaymentStatusEnum::Completed,
            'paid_date' => now(),
        ]);
    }

    /**
     * Marcar el pago como vencido.
     */
    public function markAsOverdue(): void
    {
        $this->update(['status' => PaymentStatusEnum::Pending]);
    }

    /**
     * Marcar el pago como cancelado.
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => PaymentStatusEnum::Cancelled]);
    }

    /**
     * Calcular el monto en dólares.
     */
    public function getAmountInDollarsAttribute(): float
    {
        return $this->amount_cents / 100;
    }

    /**
     * Verificar si el pago está asociado con una invoice de Stripe.
     */
    public function hasStripeInvoice(): bool
    {
        return !is_null($this->stripe_invoice_id);
    }

    /**
     * Sincronizar el estado del pago con una invoice de Stripe.
     *
     * @param \Stripe\Invoice $invoice
     */
    public function syncWithStripeInvoice($invoice): void
    {
        $this->update([
            'stripe_invoice_id' => $invoice->id,
            'status' => $this->mapStripeInvoiceStatus($invoice->status),
            'paid_date' => $invoice->status === 'paid' ? now() : null,
        ]);
    }

    /**
     * Mapear el estado de una invoice de Stripe a un estado local.
     *
     * @param string $stripeStatus
     * @return PaymentStatusEnum
     */
    protected function mapStripeInvoiceStatus(string $stripeStatus): PaymentStatusEnum
    {
        return match ($stripeStatus) {
            'paid' => PaymentStatusEnum::Completed,
            'open', 'draft' => PaymentStatusEnum::Pending,
            'overdue' => PaymentStatusEnum::Uncollectible,
            'void' => PaymentStatusEnum::Cancelled,
            default => PaymentStatusEnum::Unknown,
        };
    }
}
