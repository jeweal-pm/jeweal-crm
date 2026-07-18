<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;


class LoginController extends Controller
{
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
 
        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user->is_active === false) {
                Auth::guard('web')->logout();

                return back()->withErrors([
                    'email' => 'Your account is inactive.',
                ])->onlyInput('email');
            }

            $this->ensureUserHasPermissionRole($user);
            $request->session()->regenerate();
 
            return redirect()->intended('dashboard');
        }
 
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }


    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/crm-login-system');
    }

    private function ensureUserHasPermissionRole($user): void
    {
        if (! $user->primary_role_id) {
            $rootRole = Role::findByName('root');
            $user->forceFill(['primary_role_id' => $rootRole->id])->save();
        }

        DB::table('model_has_roles')->updateOrInsert([
            'role_id' => $user->primary_role_id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ], []);
    }
}
