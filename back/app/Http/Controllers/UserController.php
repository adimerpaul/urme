<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)
            ->with('permissions:id,name')
            ->first();

        if (!$user || !password_verify($request->password, $user->password)) {
            return response()->json(['message' => 'Usuario o contraseña incorrectos'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token'                => $token,
            'user'                 => $user,
            'must_change_password' => password_verify('123456', $user->password),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function me(Request $request)
    {
        $user = User::where('id', $request->user()->id)
            ->with('permissions:id,name')
            ->first();
        return response()->json($user);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'password_actual'             => 'required|string',
            'password_nuevo'              => 'required|string|min:6',
            'password_nuevo_confirmation' => 'required|same:password_nuevo',
        ]);

        $user = $request->user();

        if (!password_verify($request->password_actual, $user->getAuthPassword())) {
            return response()->json(['message' => 'La contraseña actual es incorrecta'], 422);
        }

        $user->update(['password' => bcrypt($request->password_nuevo)]);
        return response()->json(['message' => 'Contraseña actualizada correctamente']);
    }

    // ── CRUD usuarios ────────────────────────────────────────

    public function index()
    {
        return User::with('permissions:id,name')
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
            'email'    => 'nullable|email|max:255',
            'celular'  => 'nullable|string|max:50',
            'ci'       => 'nullable|string|max:50',
        ]);

        $user = User::create([
            'name'     => mb_strtoupper($request->name),
            'username' => $request->username,
            'email'    => $request->email,
            'celular'  => $request->celular,
            'ci'       => $request->ci,
            'password' => bcrypt($request->password),
        ]);

        return response()->json($user->load('permissions:id,name'), 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'    => 'sometimes|required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'celular' => 'nullable|string|max:50',
            'ci'      => 'nullable|string|max:50',
        ]);

        $data = $request->only(['name', 'email', 'celular', 'ci']);
        if (isset($data['name'])) {
            $data['name'] = mb_strtoupper($data['name']);
        }

        $user->update($data);
        return response()->json($user->load('permissions:id,name'));
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();
        return response()->json(['message' => 'Usuario eliminado']);
    }

    public function resetPassword($id)
    {
        User::findOrFail($id)->update(['password' => bcrypt('123456')]);
        return response()->json(['message' => 'Contraseña restablecida a 123456']);
    }

    // ── Permisos ─────────────────────────────────────────────

    public function permissions()
    {
        return response()->json(Permission::orderBy('name')->get());
    }

    public function userPermissions($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user->permissions()->pluck('id'));
    }

    public function updateUserPermissions(Request $request, $id)
    {
        $user  = User::findOrFail($id);
        $perms = Permission::whereIn('id', $request->permissions ?? [])->get();
        $user->syncPermissions($perms);
        return response()->json($user->permissions()->pluck('name'));
    }
}
