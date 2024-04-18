<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Create User
     * @param Request $request
     * @return User 
     */
    public function register(Request $request)
    {
        try {
            //Validated
            $validateUser = Validator::make(
                $request->all(),
                [
                    'name' => 'required|min:3',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required|confirmed|min:6',
                ]
            );

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            $user->token = $user->createToken("API TOKEN")->plainTextToken;

            return response([
                'status' => true,
                'message' => 'User Created Successfully',
                'results' => $user

            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Login The User
     * @param Request $request
     * @return User
     */
    public function login(Request $request)
    {

        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'email' => 'required|email',
                    'password' => 'required'
                ]
            );

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }
            if (!Auth::attempt($request->only(['email', 'password']))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email & Password does not match with our record.',
                    'errors' => [
                        'email' => ['Email & Password does not match with our record.']
                    ]
                ], 401);
            }

            $user = User::where('email', $request->email)->first();

            $user = auth()->user();
            $user->token = $user->createToken("API TOKEN")->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'results' => $user,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function abilities()
    {
        $abilities = request()->abilities ?? [];

        $user = auth()->user();
        $results = [];

        foreach ($abilities as $ability) {
            $results[$ability] = $user->can($ability);
        }

        return response(['results' => $results]);
    }

    public function passwordResetLink(Request $request)
    {

        $validateUser = Validator::make(
            $request->all(),
            [
                'email' => 'required|email|exists:users',
            ]
        );

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validateUser->errors()
            ], 401);
        }

        $token = Str::random(64);

        DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);

        Mail::send('emails.forgetPassword', ['token' => $token], function ($message) use ($request) {
            $message->to($request->email);
            $message->subject('Reset Password');
        });

        return response(['message' => 'We have e-mailed your password reset link!']);
    }

    public function getEmail(Request $request)
    {
        $password_reset = DB::table('password_resets')
            ->where([
                'token' => $request->token
            ])
            ->first();

        if (!$password_reset)
            return response(['message' => 'Invalid token!'], 422);

        return response(['results' => $password_reset], 200);
    }

    public function passwordSet(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required|confirmed|min:6',
        ]);

        $password_reset = DB::table('password_resets')
            ->where([
                'email' => $request->email,
                'token' => $request->token
            ])
            ->first();

        if (!$password_reset)
            return response(['message' => 'Invalid token!'], 422);


        $user = User::where('email', $request->email)->first();
        $user->update(['password' => Hash::make($request->password)]);

        $user = User::where('email', $request->email)->first();
        $user->token = $user->createToken("API TOKEN")->plainTextToken;

        DB::table('password_resets')->where(['email' => $request->email])->delete();

        return response(['message' => 'Your password has been changed!', 'results' => $user], 200);
    }

    /**
     * Logout the User
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if ($user)
                $user->currentAccessToken()->delete();

            return response()->json([
                'status' => true,
                'message' => 'User Logged Out Successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
