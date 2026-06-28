<?php

namespace App\Http\Controllers;

//use App\Mail\UserCreatedMail;
use App\Models\HerramientaUsuario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Spatie\Permission\Models\Permission;

class UserController extends Controller{
    function userSubpartidas($userId){
        $user = User::findOrFail($userId);
        return $user->subpartidas()->pluck('subpartida_id');
    }
    function syncUserSubpartidas(Request $request, $userId){
        $user = User::findOrFail($userId);
        $user->subpartidas()->sync($request->subpartidas ?? []);
        return response()->json(['message' => 'Subpartidas actualizadas']);
    }
    function updateUserPermissions(Request $request, $userId){
        $user = User::findOrFail($userId);
        $permissions = Permission::whereIn('id', $request->permissions)->get();
        $user->syncPermissions($permissions);
        return $user->permissions()->pluck('name');
    }
    function userPermissions(Request $request, $userId){
        $user = User::findOrFail($userId);
        return $user->permissions()->pluck('id');
    }
    function permissions(){
        return  Permission::all();
    }
    public function updateAvatar(Request $request, $userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $path = public_path('images/' . $filename);

            // Crear instancia del gestor de imágenes
            $manager = new ImageManager(new Driver()); // O new Imagick\Driver()

            // Redimensionar y comprimir
            $manager->read($file->getPathname())
                ->resize(300, 300) // o no pongas resize si no quieres cambiar tamaño
                ->toJpeg(70)       // calidad 70%
                ->save($path);

            $user->avatar = $filename;
            $user->save();

            return response()->json(['message' => 'Avatar actualizado', 'avatar' => $filename]);
        }

        return response()->json(['message' => 'No se ha enviado un archivo'], 400);
    }
    public function updateFirma(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        if (!$request->hasFile('firma')) {
            return response()->json(['message' => 'No se envió archivo'], 400);
        }

        $file = $request->file('firma');
        $filename = 'firma_' . $userId . '_' . time() . '.png';
        $path = public_path('images/' . $filename);

        $manager = new ImageManager(new Driver());
        $manager->read($file->getPathname())
            ->toJpeg(90)
            ->save($path);

        $user->firma = $filename;
        $user->save();

        return response()->json(['message' => 'Firma guardada', 'firma' => $filename]);
    }

    public function updateSello(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        if (!$request->hasFile('sello')) {
            return response()->json(['message' => 'No se envió archivo'], 400);
        }

        $file = $request->file('sello');
        $filename = 'sello_' . $userId . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = public_path('images/' . $filename);

        $manager = new ImageManager(new Driver());
        $manager->read($file->getPathname())
            ->scaleDown(500, 500)
            ->toJpeg(85)
            ->save($path);

        $user->sello = $filename;
        $user->save();

        return response()->json(['message' => 'Sello guardado', 'sello' => $filename]);
    }

    function login(Request $request){
        $credentials = $request->only('username', 'password');
        $user = User::where('username', $credentials['username'])
            ->with('permissions:id,name')
            ->with('establecimiento')
            ->with('area')
            ->with('unidad')
            ->first();
        if (!$user || !password_verify($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Usuario o contraseña incorrectos',
            ], 401);
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'token'               => $token,
            'user'                => $user,
            'must_change_password' => password_verify('123456', $user->password),
        ]);
    }

    public function registroEstado()
    {
        $inicio     = HerramientaUsuario::obtener('fecha_inicio_registro');
        $fin        = HerramientaUsuario::obtener('fecha_fin_registro');
        $habilitado = HerramientaUsuario::registroHabilitado();

        return response()->json([
            'habilitado'    => $habilitado,
            'fecha_inicio'  => $inicio,
            'fecha_fin'     => $fin,
        ]);
    }

    public function usernamePreview(Request $request)
    {
        $name  = $request->input('name', '');
        $words = array_values(array_filter(preg_split('/\s+/', strtolower(trim($name)))));

        if (empty($words)) {
            return response()->json(['username' => '']);
        }

        $base     = $words[0];
        $second   = isset($words[1]) ? substr($words[1], 0, 1) : '';
        $username = $base . $second;

        $original = $username;
        $counter  = 2;
        while (User::where('username', $username)->exists()) {
            $username = $original . $counter++;
        }

        return response()->json(['username' => $username]);
    }

    public function register(Request $request)
    {
        if (! HerramientaUsuario::registroHabilitado()) {
            return response()->json([
                'message' => 'No se puede crear la cuenta en este momento, por favor comuníquese con el administrador.',
            ], 403);
        }

        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'nullable|email|max:255',
            'celular'   => 'nullable|string|max:50',
            'ci'        => 'required|string|max:50',
            'unidad_id' => 'nullable|exists:unidades,id',
        ]);

        // Generar username: primera palabra + primera letra de segunda palabra
        $words    = preg_split('/\s+/', strtolower(trim($request->name)));
        $base     = $words[0] ?? 'usuario';
        $second   = isset($words[1]) ? substr($words[1], 0, 1) : '';
        $username = $base . $second;

        // Manejar duplicados
        $original = $username;
        $counter  = 2;
        while (User::where('username', $username)->exists()) {
            $username = $original . $counter++;
        }

        $user = User::create([
            'name'      => $request->name,
            'username'  => $username,
            'email'     => $request->email,
            'celular'   => $request->celular,
            'ci'        => $request->ci,
            'role'      => 'Almacén',
            'password'  => bcrypt($request->ci),
            'unidad_id' => $request->unidad_id,
        ]);

        $perms = Permission::whereIn('name', ['Ver Pedidos', 'Crear Pedidos'])->get();
        $user->syncPermissions($perms);

        // Asignar subpartida Papel 32100 por defecto
        $subpartida = \App\Models\Subpartida::where('codigo', '32100')->first();
        if ($subpartida) {
            $user->subpartidas()->attach($subpartida->id);
        }

        return response()->json([
            'username' => $username,
            'password' => $request->ci,
        ], 201);
    }

    public function resetPassword($id)
    {
        $user = User::findOrFail($id);
        $user->update(['password' => bcrypt('123456')]);
        return response()->json(['message' => 'Contraseña restablecida a 123456']);
    }
    function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Token eliminado',
        ]);
    }
    function me(Request $request){
//        $user = $request->user();
//        $user->load('permissions,establecimiento,area');
        $user = User::where('id', $request->user()->id)
            ->with('permissions:id,name')
            ->with('establecimiento')
            ->with('area')
            ->with('unidad')
            ->first();
        return response()->json($user);
    }
    function index(){
        return User::where('id', '!=', 0)
            ->with('permissions:id,name')
            ->with('establecimiento')
            ->with('area')
            ->with('unidad')
            ->orderBy('id', 'desc')
            ->get();
    }
    function update(Request $request, $id){
        $user = User::find($id);
        $request->validate([
            'celular' => 'nullable|string|max:50',
        ]);
        $user->update($request->except('password'));
        error_log('User' . json_encode($user));
        return $user;
    }
    function updatePassword(Request $request, $id){
        $user = User::find($id);
        $user->update([
            'password' => bcrypt($request->password),
        ]);
        return $user;
    }

    public function updatePerfil(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'name'          => 'sometimes|required|string|max:255',
            'email'         => 'nullable|email|max:255',
            'celular'       => 'nullable|string|max:50',
            'ci'            => 'nullable|string|max:50',
            'mostrar_firma' => 'nullable|boolean',
            'mostrar_sello' => 'nullable|boolean',
        ]);
        $user->update($request->only(['name', 'email', 'celular', 'ci', 'mostrar_firma', 'mostrar_sello']));
        return response()->json($user);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'password_actual'              => 'required|string',
            'password_nuevo'               => 'required|string|min:6',
            'password_nuevo_confirmation'  => 'required|same:password_nuevo',
        ]);

        $user = $request->user();
        if (!password_verify($request->password_actual, $user->getAuthPassword())) {
            return response()->json(['message' => 'La contraseña actual es incorrecta'], 422);
        }

        $user->update(['password' => bcrypt($request->password_nuevo)]);
        return response()->json(['message' => 'Contraseña actualizada correctamente']);
    }
    function store(Request $request){
        $validatedData = $request->validate([
            'username' => 'required',
            'password' => 'required',
            'name' => 'required',
            'celular' => 'nullable|string|max:50',
//            'email' => 'required|email|unique:users',
        ]);
        if (User::where('username', $request->username)->exists()) {
            return response()->json(['message' => 'El nombre de usuario ya existe'], 422);
        }
        $user = User::create($request->all());
        return $user;
    }
    function destroy($id){
        return User::destroy($id);
    }
    public function getPermissions($userId)
    {
        $user = User::findOrFail($userId);
        // devuelve IDs de permisos del usuario
        return $user->permissions()->pluck('id');
    }

    public function syncPermissions(Request $request, $userId)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $user = User::findOrFail($userId);
        $perms = Permission::whereIn('id', $request->permissions ?? [])->get();
        $user->syncPermissions($perms);

        return response()->json([
            'message' => 'Permisos actualizados',
            'permissions' => $user->permissions()->pluck('name'),
        ]);
    }
}
