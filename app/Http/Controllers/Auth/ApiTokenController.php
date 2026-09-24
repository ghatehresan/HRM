<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiTokenRequest;
use App\Http\Resources\ApiTokenResource;
use App\Models\AuditLog;
use App\Services\AuthResult;
use App\Services\AuthService;
use App\Services\MfaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApiTokenController extends Controller
{
    /**
     * Issue a Bearer token from credentials (+ MFA code when the
     * account is MFA-bound). Same lockout/inactive rules as web login.
     */
    public function store(ApiTokenRequest $request, AuthService $auth, MfaService $mfa): JsonResponse
    {
        $result = $auth->attempt(
            (string) $request->validated('email'),
            (string) $request->validated('password'),
        );

        if ($result->status === AuthResult::LOCKED) {
            return response()->json(['message' => 'حساب قفل شده است؛ بعداً تلاش کنید.'], 423);
        }

        if (! $result->succeeded() && ! $result->needsMfa()) {
            return response()->json(['message' => 'مشخصات ورود اشتباه است.'], 401);
        }

        $user = $result->user;

        if ($user === null) {
            return response()->json(['message' => 'مشخصات ورود اشتباه است.'], 401);
        }

        if ($result->needsMfa()) {
            $code = $request->validated('code');

            if (! is_string($code) || $code === '' || ! $mfa->verifyChallenge($user, $code)) {
                return response()->json(['message' => 'کد تأیید دومرحله‌ای لازم است یا اشتباه است.'], 422);
            }
        }

        $expiresAt = now()->addDays((int) config('hrm.auth.api_token_ttl_days', 30));
        $token = $user->createToken((string) $request->validated('name'), ['*'], $expiresAt);

        $user->forceFill(['last_login_at' => now()])->save();
        AuditLog::record('token_issued', $user, null, ['name' => $token->accessToken->name]);

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
        ], 201);
    }

    public function index(): AnonymousResourceCollection
    {
        $tokens = $this->authedUser()->tokens()->latest('id')->get();

        return ApiTokenResource::collection($tokens);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = $this->authedUser();

        // Scoped to the caller's own tokens: foreign ids 404 without
        // revealing whether they exist.
        $token = $user->tokens()->where('id', $id)->first();

        if ($token === null) {
            abort(404);
        }

        $name = $token->name;
        $token->delete();

        AuditLog::record('token_revoked', $user, null, ['name' => $name]);

        return response()->json(['message' => 'توکن باطل شد.']);
    }
}
