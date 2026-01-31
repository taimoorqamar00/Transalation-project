<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Info(
 *      title="Translation Management API",
 *      version="1.0.0",
 *      description="High-performance translation management service with multi-locale support, caching, and scalable architecture.",
 *      @OA\Contact(
 *          email="support@translation-api.com"
 *      ),
 *      @OA\License(
 *          name="MIT",
 *          url="https://opensource.org/licenses/MIT"
 *      )
 * )
 * 
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Translation API Server"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="API authentication endpoints"
 * )
 * 
 * @OA\SecurityScheme(
 *     type="http",
 *     securityScheme="sanctum",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your Bearer token"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/login",
     *      operationId="loginUser",
     *      tags={"Authentication"},
     *      summary="Authenticate user and generate token",
     *      description="Authenticates a user with email and password and returns a Bearer token for API access.",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email","password"},
     *              @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
     *              @OA\Property(property="password", type="string", format="password", example="password123")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful authentication",
     *          @OA\JsonContent(
     *              @OA\Property(property="token", type="string", example="1|abc123def456ghi789jkl012mno345pqr")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Invalid credentials",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Invalid credentials")
     *          )
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="The given data was invalid."),
     *              @OA\Property(
     *                  property="errors",
     *                  type="object",
     *                  @OA\Property(property="email", type="array", @OA\Items(type="string"))
     *              )
     *          )
     *      )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($validated)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $request->user()->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => $request->user()
            ]
        ]);
    }
}
