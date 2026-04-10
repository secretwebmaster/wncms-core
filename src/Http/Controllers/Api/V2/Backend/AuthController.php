<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends ApiV2Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $userModel = wncms()->getModelClass('user');
        $user = $userModel::query()->where('email', $request->string('email'))->first();

        if (!$user || !Hash::check((string) $request->input('password'), (string) $user->password)) {
            return $this->error(__('auth.failed'), Response::HTTP_UNAUTHORIZED);
        }

        $deviceName = (string) $request->input('device_name', 'nextjs-admin');
        $token = $this->issueAccessToken($user, $deviceName);

        return $this->ok([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ],
        ], 'login_success');
    }

    public function logout(Request $request)
    {
        $tokenId = (int) $request->attributes->get('api_v2_token_id', 0);
        if ($tokenId > 0) {
            DB::table('personal_access_tokens')->where('id', $tokenId)->delete();
        }

        return $this->ok(null, 'logout_success');
    }

    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->error(__('auth.unauthenticated'), Response::HTTP_UNAUTHORIZED);
        }

        return $this->ok([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'roles' => method_exists($user, 'roles') ? $user->roles()->pluck('name')->all() : [],
        ]);
    }

    protected function issueAccessToken($user, string $deviceName): string
    {
        if (method_exists($user, 'createToken')) {
            return $user->createToken($deviceName)->plainTextToken;
        }

        $plainTextToken = bin2hex(random_bytes(40));
        $tokenId = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => get_class($user),
            'tokenable_id' => $user->id,
            'name' => $deviceName,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => json_encode(['*']),
            'last_used_at' => null,
            'expires_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $tokenId . '|' . $plainTextToken;
    }
}
