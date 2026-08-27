<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Muestra el formulario de inicio de sesión.
     */
    public function index()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    /**
     * Procesa el inicio de sesión contra la tabla `usuarios`.
     *
     * El identificador de la cuenta es el DNI de la persona asociada y
     * la contraseña es la columna `clave` (hash de bcrypt).
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'dni' => 'required|string',
            'clave' => 'required|string',
        ], [], [
            'dni' => 'DNI',
            'clave' => 'contraseña',
        ]);

        // Busca la cuenta cuyo usuario esté vinculado a la persona del DNI dado.
        $usuario = Usuario::whereHas('persona', function ($query) use ($data) {
            $query->where('dni', $data['dni']);
        })->with('persona')->get()->first();

        if ($usuario && $usuario->verificarClave($data['clave'])) {
            Auth::login($usuario);

            return redirect()->route('home');
        }

        return redirect()->route('login')
            ->withErrors('Datos de inicio de sesión incorrectos');
    }

    /**
     * Cierra la sesión del usuario autenticado.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
