<?php

namespace App\Http\Controllers;

use App\Enums\BankEnum;
use App\Enums\NetworkCodeEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MBNotificaController extends Controller
{
    public function notificarTransaccion(Request $request)
    {
        Log::info('Iniciando notificación de transacción', ['data' => $request->all()]);

        // Verificar si el token de autorización es válido
        $authorizationHeader = $request->header('Authorization');
        if (! $authorizationHeader || $authorizationHeader !== config('banking.token_key')) {
            Log::warning('Token de autorización inválido o ausente', ['header' => $authorizationHeader]);

            return response()->json(['abono' => false], 401);
        }

        // Validar los datos del request
        try {
            $request->validate([
                'IdComercio' => 'required|string|size:8',
                'TelefonoComercio' => 'required|string|size:11',
                'TelefonoEmisor' => 'required|string|size:11',
                'BancoEmisor' => 'required|string|size:3',
                'Monto' => 'required|regex:/^\d+(\.\d{1,2})?$/',
                'FechaHora' => [
                    'required',
                    'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/',
                ],
                'Referencia' => 'required|string|max:50',
                'CodigoRed' => 'required|string|max:2',
                'Concepto' => 'nullable|string|max:30',
            ]);
        } catch (\Exception $e) {
            Log::error('Error en la validación de datos', ['error' => $e->getMessage()]);

            return response()->json(['abono' => false]);
        }
        Log::info('Validación de datos completada', ['data' => $request->all()]);

        // Ajustar el código del banco
        $codigoBanco = str_pad($request->BancoEmisor, 4, '0', STR_PAD_LEFT);
        Log::info('Código de banco ajustado', ['codigoBanco' => $codigoBanco]);

        // Validar el código del banco usando el enum BankEnum
        $bancoValido = collect(BankEnum::cases())->first(fn ($enum) => $enum->value === $codigoBanco);
        if (! $bancoValido) {
            Log::warning('Código de banco inválido', ['BancoEmisor' => $codigoBanco]);

            return response()->json(['abono' => false]);
        }

        // Validar el código de red usando el enum NetworkCodeEnum
        $codigoRedValido = NetworkCodeEnum::tryFrom($request->CodigoRed) !== null;
        if (! $codigoRedValido) {
            Log::warning('Código de red inválido', ['CodigoRed' => $request->CodigoRed]);

            return response()->json(['abono' => false]);
        }

        // Validación adicional (puedes extender este bloque según lo que necesites)
        $abono = ($request->CodigoRed === NetworkCodeEnum::Approved->value);
        Log::info('Finalizando notificación de transacción', ['abono' => $abono]);

        return response()->json(['abono' => $abono]);
    }
}
