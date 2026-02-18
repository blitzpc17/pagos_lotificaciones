<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;

class AjaxLoginController extends Controller
{
    public function show()
    {
        return view('auth.login'); // tu blade del login
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required','string'],
            'password' => ['required','string'],
            'remember' => ['nullable'],
        ]);

        $login = trim($data['login']);

        // Detecta si es email o username
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // IMPORTANTE: incluir 'password' siempre
        $credentials = [
            $field => $login,
            'password' => $data['password'],
            'baja' => false,
            'is_active' => true,
        ];

        if (!auth()->attempt($credentials, (bool)($data['remember'] ?? false))) {
            return response()->json(['ok'=>false,'message'=>'Credenciales inválidas.'], 422);
        }

        $request->session()->regenerate();

        // auditoría opcional
        if (class_exists(\App\Services\AuditService::class)) {
            AuditService::log(
                usuarioId: auth()->id(),
                accion: 'LOGIN',
                tabla: 'usuarios',
                registroId: auth()->id(),
                before: null,
                after: ['login' => $login],
                request: $request
            );
        }

        return response()->json([
            'ok' => true,
            'redirect' => route('dashboard')
        ]);
    }

    public function logout(Request $request)
    {
        if (auth()->check() && class_exists(\App\Services\AuditService::class)) {
            AuditService::log(
                usuarioId: auth()->id(),
                accion: 'LOGOUT',
                tabla: 'usuarios',
                registroId: auth()->id(),
                before: null,
                after: null,
                request: $request
            );
        }

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok'=>true,'redirect'=>route('login')]);
    }
}
