<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Standard secure authentication or simulated vulnerable SQLi toggle.
     */
    public function login(Request $request): JsonResponse
    {
        $email = $request->input('email', '');
        $password = $request->input('password', '');
        $vulnerable = $request->boolean('vulnerable', false);

        if ($vulnerable) {
            // OWASP SQL Injection demonstration mode
            // Dynamically constructing raw SQL string demonstrates injection
            try {
                $rawQuery = "SELECT id, name, email, password FROM users WHERE email = '{$email}' AND password = '{$password}'";
                $results = DB::select($rawQuery);

                if (!empty($results)) {
                    $matchedUser = $results[0];
                    return response()->json([
                        'success' => true,
                        'message' => 'Simulated result: logged in as admin. Your input changed the SQL query structure.',
                        'user' => [
                            'id' => $matchedUser->id,
                            'name' => $matchedUser->name,
                            'email' => $matchedUser->email,
                        ],
                        'query_executed' => $rawQuery,
                        'vulnerability' => 'SQLi (CWE-89)',
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => "Simulated result: no user matches. Try an email that makes the WHERE clause always true: ' OR '1'='1",
                    'query_executed' => $rawQuery,
                ], 401);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'SQL Error: ' . $e->getMessage(),
                    'query_executed' => $rawQuery ?? null,
                ], 400);
            }
        }

        // Standard Secure Flow using Parameterized Eloquent
        $user = User::where('email', $email)->first();

        if ($user && Hash::check($password, $user->password)) {
            $token = $user->createToken('casefile-investigator')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Signed in. Clearance verified as ' . $user->name,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Email or password is incorrect. Check both and try again.',
        ], 401);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }
}

