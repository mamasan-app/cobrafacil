<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Collection;

enum BankEnum: string implements HasLabel
{
    case Venezuela = '0102';
    case VenezolanoDeCredito = '0104';
    case Mercantil = '0105';
    case Provincial = '0108';
    case DelCaribe = '0114';
    case Exterior = '0115';
    case Caroni = '0128';
    case Banesco = '0134';
    case Sofitasa = '0137';
    case Plaza = '0138';
    case FondoComun = '0151';
    case Banco100 = '0156';
    case DelSur = '0157';
    case DelTesoro = '0163';
    case Bancrecer = '0168';
    case MiBanco = '0169';
    case Activo = '0171';
    case Bancamiga = '0172';
    case Banplus = '0174';
    case Bicentenario = '0175';
    case BanFanb = '0177';
    case NacionalDeCredito = '0191';
    case InstitutoCreditoPopular = '0601';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Venezuela => "$this->value - Banco de Venezuela, S.A. Banco Universal",
            self::VenezolanoDeCredito => "$this->value - Banco Venezolano de Crédito, S.A. Banco Universal",
            self::Mercantil => "$this->value - Banco Mercantil C.A., Banco Universal",
            self::Provincial => "$this->value - Banco Provincial, S.A. Banco Universal",
            self::DelCaribe => "$this->value - Banco del Caribe C.A., Banco Universal",
            self::Exterior => "$this->value - Banco Exterior C.A., Banco Universal",
            self::Caroni => "$this->value - Banco Caroní C.A., Banco Universal",
            self::Banesco => "$this->value - Banesco Banco Universal, C.A.",
            self::Sofitasa => "$this->value - Banco Sofitasa Banco Universal, C.A.",
            self::Plaza => "$this->value - Banco Plaza, Banco Universal",
            self::FondoComun => "$this->value - Banco Fondo Común, C.A Banco Universal",
            self::Banco100 => "$this->value - 100% Banco, Banco Comercial, C.A.",
            self::DelSur => "$this->value - DelSur, Banco Universal C.A.",
            self::DelTesoro => "$this->value - Banco del Tesoro C.A., Banco Universal",
            self::Bancrecer => "$this->value - Bancrecer S.A., Banco Microfinanciero",
            self::MiBanco => "$this->value - Mi Banco, Banco Microfinanciero, C.A.",
            self::Activo => "$this->value - Banco Activo C.A., Banco Universal",
            self::Bancamiga => "$this->value - Bancamiga Banco Universal, C.A.",
            self::Banplus => "$this->value - Banplus Banco Universal, C.A.",
            self::Bicentenario => "$this->value - Banco Bicentenario del Pueblo, Banco Universal C.A.",
            self::BanFanb => "$this->value - Banco de la Fuerza Armada Nacional Bolivariana, B.U.",
            self::NacionalDeCredito => "$this->value - Banco Nacional de Crédito C.A., Banco Universal",
            self::InstitutoCreditoPopular => "$this->value - Instituto Municipal de Crédito Popular",
        };
    }

    public static function all(): Collection
    {
        return collect([
            self::Venezuela,
            self::VenezolanoDeCredito,
            self::Mercantil,
            self::Provincial,
            self::DelCaribe,
            self::Exterior,
            self::Caroni,
            self::Banesco,
            self::Sofitasa,
            self::Plaza,
            self::FondoComun,
            self::Banco100,
            self::DelSur,
            self::DelTesoro,
            self::Bancrecer,
            self::MiBanco,
            self::Activo,
            self::Bancamiga,
            self::Banplus,
            self::Bicentenario,
            self::BanFanb,
            self::NacionalDeCredito,
            self::InstitutoCreditoPopular,
        ]);
    }
}
