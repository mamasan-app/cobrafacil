<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'plans';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'description',
        'price_cents',
        'published',
        'featured',
        'frequency_id',
        'store_id',
        'free_days',
        'grace_period',
        'infinite_duration',
        'duration',
        'stripe_product_id',
    ];

    protected $casts = [
        'published' => 'boolean',
        'featured' => 'boolean',
        'price_cents' => 'integer',
        'infinite_duration' => 'boolean',
        'duration' => 'integer',
    ];

    protected $appends = [
        'price',
        'duration_text',
    ];

    /**
     * Atributo calculado para el precio en formato decimal.
     */
    public function price(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->price_cents / 100,
            set: fn (float $value): array => [
                'price_cents' => $value * 100,
            ]
        );
    }

    /**
     * Atributo para formatear el precio como un string con dos decimales.
     */
    public function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: fn (): string => number_format($this->price, 2),
        );
    }

    /**
     * Método para obtener el precio formateado con la moneda.
     */
    public function getFormattedPrice(): string
    {
        return number_format($this->price, 2).' USD';  // Devuelve el precio con dos decimales y la etiqueta "USD"
    }

    /**
     * Atributo para verificar si el servicio está publicado.
     */
    public function isPublished(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->published,
        );
    }

    /**
     * Relación de muchos a uno con 'Frequency'.
     */
    public function frequency()
    {
        return $this->belongsTo(Frequency::class, 'frequency_id');
    }

    public function getFrequencyDays(): int
    {
        return Frequency::where('id', $this->frequency_id)->value('days_count') ?? 0;
    }

    /**
     * Relación muchos a muchos con 'Address'.
     */
    public function addresses()
    {
        return $this->belongsToMany(Address::class, 'address_plan', 'plan_id', 'address_id');
    }

    /**
     * Relación de uno a muchos con 'Store'.
     *
     * Relación directa con la tabla 'stores' usando 'store_id'.
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Atributo que verifica si la duración es infinita.
     */
    public function isInfiniteDuration(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->infinite_duration,
        );
    }

    /**
     * Atributo que calcula la duración del plan.
     * Si es infinito, devuelve 'Infinito', de lo contrario devuelve la duración en días.
     */
    public function getDurationTextAttribute(): string
    {
        if ($this->infinite_duration) {
            return 'Infinito';
        }

        return $this->duration.' días';
    }
}
