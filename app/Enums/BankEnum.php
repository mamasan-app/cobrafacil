<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Collection;

enum BankEnum: string implements HasLabel
{
    case Venezuela = 'Venezuela';
    case VenezolanoDeCredito = 'VenezolanoDeCredito';
    case Mercantil = 'Mercantil';
    case Provincial = 'Provincial';
    case DelCaribe = 'DelCaribe';
    case Exterior = 'Exterior';
    case Caroni = 'Caroni';
    case Banesco = 'Banesco';
    case Sofitasa = 'Sofitasa';
    case Plaza = 'Plaza';
    case FondoComun = 'FondoComun';
    case Banco100 = 'Banco100';
    case DelSur = 'DelSur';
    case DelTesoro = 'DelTesoro';
    case Bancrecer = 'Bancrecer';
    case MiBanco = 'MiBanco';
    case Activo = 'Activo';
    case Bancamiga = 'Bancamiga';
    case Banplus = 'Banplus';
    case Bicentenario = 'Bicentenario';
    case BanFanb = 'BanFanb';
    case NacionalDeCredito = 'NacionalDeCredito';
    case InstitutoCreditoPopular = 'InstitutoCreditoPopular';

    public function code(): ?string
    {
        return match ($this) {
            self::Venezuela => '0102',
            self::VenezolanoDeCredito => '0104',
            self::Mercantil => '0105',
            self::Provincial => '0108',
            self::DelCaribe => '0114',
            self::Exterior => '0115',
            self::Caroni => '0128',
            self::Banesco => '0134',
            self::Sofitasa => '0137',
            self::Plaza => '0138',
            self::FondoComun => '0151',
            self::Banco100 => '0156',
            self::DelSur => '0157',
            self::DelTesoro => '0163',
            self::Bancrecer => '0168',
            self::MiBanco => '0169',
            self::Activo => '0171',
            self::Bancamiga => '0172',
            self::Banplus => '0174',
            self::Bicentenario => '0175',
            self::BanFanb => '0177',
            self::NacionalDeCredito => '0191',
            self::InstitutoCreditoPopular => '0601',
        };
    }

    public function getLabel(): ?string
    {
        return $this->code().' - '.match ($this) {
            self::Venezuela => 'Banco de Venezuela, S.A. Banco Universal',
            self::VenezolanoDeCredito => 'Banco Venezolano de Crédito, S.A. Banco Universal',
            self::Mercantil => 'Banco Mercantil C.A., Banco Universal',
            self::Provincial => 'Banco Provincial, S.A. Banco Universal',
            self::DelCaribe => 'Banco del Caribe C.A., Banco Universal',
            self::Exterior => 'Banco Exterior C.A., Banco Universal',
            self::Caroni => 'Banco Caroní C.A., Banco Universal',
            self::Banesco => 'Banesco Banco Universal, C.A.',
            self::Sofitasa => 'Banco Sofitasa Banco Universal, C.A.',
            self::Plaza => 'Banco Plaza, Banco Universal',
            self::FondoComun => 'Banco Fondo Común, C.A Banco Universal',
            self::Banco100 => '100% Banco, Banco Comercial, C.A.',
            self::DelSur => 'DelSur, Banco Universal C.A.',
            self::DelTesoro => 'Banco del Tesoro C.A., Banco Universal',
            self::Bancrecer => 'Bancrecer S.A., Banco Microfinanciero',
            self::MiBanco => 'Mi Banco, Banco Microfinanciero, C.A.',
            self::Activo => 'Banco Activo C.A., Banco Universal',
            self::Bancamiga => 'Bancamiga Banco Universal, C.A.',
            self::Banplus => 'Banplus Banco Universal, C.A.',
            self::Bicentenario => 'Banco Bicentenario del Pueblo, Banco Universal C.A.',
            self::BanFanb => 'Banco de la Fuerza Armada Nacional Bolivariana, B.U.',
            self::NacionalDeCredito => 'Banco Nacional de Crédito C.A., Banco Universal',
            self::InstitutoCreditoPopular => 'Instituto Municipal de Crédito Popular',
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
