<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string|regex:/^[0-9]{10}$/',
        ]);

        $result = $this->firebaseService->sendOtp($request->mobile);
        
        return response()->json($result);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string|regex:/^[0-9]{10}$/',
            'otp' => 'required|string|size:6',
        ]);

        $result = $this->firebaseService->verifyOtp($request->mobile, $request->otp);
        
        if (!$result['success']) {
            throw ValidationException::withMessages([
                'otp' => [$result['message']],
            ]);
        }
        
        return response()->json($result);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'mobile' => 'required|string|regex:/^[0-9]{10}$/|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,student',
            'mobile_verified' => 'required|boolean',
        ]);

        if (!$request->mobile_verified) {
            throw ValidationException::withMessages([
                'mobile' => ['Mobile number must be verified with OTP.'],
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'mobile_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Registration successful. Please login.',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        \Log::info('=== LOGIN REQUEST RECEIVED ===');
        \Log::info('Request Origin: ' . $request->header('Origin'));
        \Log::info('Request Method: ' . $request->method());
        \Log::info('Request Data: ' . json_encode($request->all()));
        
        try {
            $request->validate([
                'login_type' => 'required|in:email,mobile',
                'email' => 'required_if:login_type,email|email',
                'password' => 'required_if:login_type,email',
                'mobile' => 'required_if:login_type,mobile|string|regex:/^[0-9]{10}$/',
                'otp' => 'required_if:login_type,mobile|string|size:6',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed: ' . json_encode($e->errors()));
            throw $e;
        }

        $user = null;

        if ($request->login_type === 'email') {
            // Email/Password login
            if (!Auth::attempt($request->only('email', 'password'))) {
                \Log::error('Authentication failed for: ' . $request->email);
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }
            $user = Auth::user();
        } else {
            // Mobile/OTP login
            $result = $this->firebaseService->verifyOtp($request->mobile, $request->otp, true);
            
            if (!$result['success']) {
                throw ValidationException::withMessages([
                    'otp' => [$result['message']],
                ]);
            }

            $user = User::where('mobile', $request->mobile)->first();
            
            if (!$user) {
                throw ValidationException::withMessages([
                    'mobile' => ['No account found with this mobile number.'],
                ]);
            }
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        \Log::info('Login successful for user: ' . $user->email);

        return response()->json([
            'user' => $user,
            'token' => $token,
        ])->header('Access-Control-Allow-Origin', $request->header('Origin'))
          ->header('Access-Control-Allow-Credentials', 'true');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}