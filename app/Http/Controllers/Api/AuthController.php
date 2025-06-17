<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Empresa;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Utils\ResponseFormat;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Registro de nuevo usuario
     */
 public function register(Request $request)
    {
        try {
            // Validate user and new company data
            $validatedData = $request->validate([
                'name' => 'required|max:255|unique:users',
                'lastname' => 'required|max:255',
                'email' => 'required|email|unique:users',
                'phone' => 'required|string',
                'password' => 'required|min:8|confirmed',
                // New fields for the company
                'company_name' => 'required|string|max:255|unique:empresas,nombre', // Validate company name
                'company_address' => 'required|string|max:255', // Validate company address
            ]);

            // Start a database transaction
            // This ensures that if creating the user fails, the company creation is rolled back too.
            DB::beginTransaction();

            // 1. Create the new company
            $empresa = Empresa::create([
                'nombre' => $validatedData['company_name'],
                'direccion' => $validatedData['company_address'],
            ]);

            // 2. Create the new user, associating them with the newly created company
            $user = User::create([
                'name' => $validatedData['name'],
                'lastname' => $validatedData['lastname'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'empresa_id' => $empresa->id, // Assign the ID of the newly created company
                'password' => Hash::make($validatedData['password']),
                'roles' => 'supervisor', // Hardcoded to 'supervisor'
            ]);

            // Commit the transaction if both creations were successful
            DB::commit();

            // Create authentication token
            $tokenResult = $user->createToken('authToken', ['*']);
            $token = $tokenResult->plainTextToken;

            return ResponseFormat::response(201, 'Usuario y Empresa registrados con éxito', [
                'token' => $token,
                'user' => $user->load('empresa') // Optionally load the company data with the user
            ]);

        } catch (ValidationException $e) {
            // Rollback transaction if validation fails
            DB::rollBack();
            return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($e->validator));
        } catch (\Exception $e) {
            // Rollback transaction for any other exception
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Inicio de sesión
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string',
                'password' => 'required|string',
            ]);

            $user = User::where('name', $request->name)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return ResponseFormat::response(401, 'Credenciales incorrectas');
            }

            $user->tokens()->delete();

            $token = $user->createToken('authToken')->plainTextToken;

            return ResponseFormat::response(200, 'Inicio de sesión exitoso', [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone
                ]
            ]);
        } catch (ValidationException $e) {
            return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($e->validator));
        } catch (\Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Cierre de sesión
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return ResponseFormat::response(200, 'Sesión cerrada correctamente');
        } catch (\Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Obtener usuario autenticado
     */
    public function user(Request $request)
    {
        try {
            $user = $request->user();
            return ResponseFormat::response(200, 'Datos de usuario obtenidos', $user);
        } catch (\Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Actualizar perfil de usuario
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            $validatedData = $request->validate([
                'name' => 'sometimes|max:255|unique:users,name,' . $user->id,
                'lastname' => 'sometimes|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'phone' => 'sometimes|string',
                'password' => 'sometimes|min:8|confirmed',
            ]);

            if ($request->has('password')) {
                $validatedData['password'] = Hash::make($validatedData['password']);
            }

            $user->update($validatedData);

            return ResponseFormat::response(200, 'Perfil actualizado correctamente', $user);
        } catch (ValidationException $e) {
            return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($e->validator));
        } catch (\Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Obtener nombre del usuario autenticado
     */
    public function getAuthenticatedUserName(Request $request)
    {
        try {
            $name = $request->user()?->name;
            return ResponseFormat::response(200, 'Nombre de usuario obtenido', ['name' => $name]);
        } catch (\Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Solicitar restablecimiento de contraseña
     */
    public function forgotPassword(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return ResponseFormat::response(404, 'No existe un usuario con este email');
            }

            $token = Str::random(60);
            $user->forceFill([
                'remember_token' => Hash::make($token),
                'reset_token_expires_at' => now()->addHours(1)
            ])->save();

            // Aquí deberías enviar el token por email al usuario
            // Mail::to($user->email)->send(new PasswordResetMail($token));

            return ResponseFormat::response(200, 'Enlace de restablecimiento enviado');
        } catch (ValidationException $e) {
            return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($e->validator));
        } catch (\Exception $e) {
            return ResponseFormat::response(500, $e->getMessage());
        }
    }

    /**
     * Restablecer contraseña
     */
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:8|confirmed',
            ]);

            $user = User::where('email', $request->email)->first();

            if (
                !$user ||
                !$user->remember_token ||
                !Hash::check($request->token, $user->remember_token) ||
                $user->reset_token_expires_at < now()
            ) {
                return ResponseFormat::response(400, 'Token inválido o expirado');
            }

            $user->forceFill([
                'password' => Hash::make($request->password),
                'remember_token' => null,
                'reset_token_expires_at' => null
            ])->save();

            return ResponseFormat::response(200, 'Contraseña restablecida con éxito');
        } catch (ValidationException $e) {
            return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($e->validator));
        } catch (\Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }
}
