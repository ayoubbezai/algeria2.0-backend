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
        $email = $request->input('email', $request->input('badge_id', ''));
        $password = $request->input('password', $request->input('passphrase', ''));
        $vulnerable = $request->boolean('vulnerable', false);

        if ($vulnerable) {
            // OWASP SQL Injection demonstration mode
            // Dynamically constructing raw SQL string demonstrates injection
            try {
                $rawQuery = "SELECT id, name, email, password FROM users WHERE email = '{$email}' AND password = '{$password}'";
                $results = DB::select($rawQuery);

                if (!empty($results)) {
                    $matchedUser = $results[0];
                    $isUnionPayload = stripos($email, 'union') !== false || stripos($password, 'union') !== false;

                    $response = [
                        'success' => true,
                        'message' => 'Logged in via SQL injection. Query execution manipulated.',
                        'user' => [
                            'id' => $matchedUser->id,
                            'name' => $matchedUser->name,
                            'email' => $matchedUser->email,
                        ],
                        'query_executed' => $rawQuery,
                        'vulnerability' => $isUnionPayload ? 'SQLi (CWE-89) - UNION-based Data Exfiltration' : 'SQLi (CWE-89) - Authentication Bypass',
                    ];

                    // ONLY return the full exfiltrated table dump when UNION injection is performed!
                    if ($isUnionPayload) {
                        $response['table_dump'] = array_map(function ($row) {
                            return [
                                'id' => $row->id,
                                'name' => $row->name,
                                'email' => $row->email,
                                'password_hash' => $row->password,
                            ];
                        }, $results);
                        $response['records_extracted'] = count($results);
                    }

                    return response()->json($response);
                }

                return response()->json([
                    'success' => false,
                    'message' => "Query returned zero rows. Try an injection payload: ' OR '1'='1' # or ' UNION SELECT id, name, email, password FROM users #",
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

