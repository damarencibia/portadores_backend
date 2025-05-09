<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Obtener todos los usuarios.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::with('roles')->get(); // Incluir roles en la respuesta
        return response()->json($users);
    }

    /**
     * Obtener un usuario específico por ID.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::with('roles')->find($id); // Incluir roles en la respuesta

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        return response()->json($user);
    }

    /**
     * Crear un nuevo usuario.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validar los datos de entrada
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'ci' => 'required|string|unique:users|max:255',
            'address' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users|max:255',
            'phone' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Crear el usuario
        $user = User::create($request->all());

        return response()->json($user, 201);
    }

    /**
     * Actualizar un usuario existente.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Validar los datos de entrada
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'lastname' => 'sometimes|string|max:255',
            'ci' => 'sometimes|string|unique:users,ci,' . $user->id . '|max:255',
            'address' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|unique:users,email,' . $user->id . '|max:255',
            'phone' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Actualizar el usuario
        $user->update($request->all());

        return response()->json($user);
    }

    /**
     * Eliminar un usuario.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Eliminar el usuario
        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }

    /**
     * Asignar un rol a un usuario.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function assignRole(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Validar el rol
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Asignar el rol al usuario
        $user->roles()->sync([$request->role_id]);

        return response()->json(['message' => 'Rol asignado correctamente']);
    }

    /**
     * Obtener los roles de un usuario.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getUserRoles($id)
    {
        $user = User::with('roles')->find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        return response()->json($user->roles);
    }
}

