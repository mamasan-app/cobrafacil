<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use MagicLink\Actions\LoginAction;
use MagicLink\MagicLink;

class MagicLinkLoginController extends Controller
{
    /**
     * Genera y envía el enlace mágico.
     */
    public function sendMagicLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Busca el usuario por correo electrónico
        $user = User::where('email', $request->email)->firstOrFail();

        // Configura la acción de inicio de sesión y la redirección al dashboard de Filament
        $action = new LoginAction($user);
        $action->response(redirect('/app/dashboard')); // Cambia la ruta si es necesario

        // Crea el enlace mágico
        $url = MagicLink::create($action)->url;

        // Aquí puedes enviar el enlace por correo, pero para pruebas solo devolvemos el enlace en el mensaje
        return back()->with('message', "Se ha enviado un enlace de acceso a tu correo: $url");
    }
}
