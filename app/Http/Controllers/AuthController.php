<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FirebaseService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $firebaseService;
    protected $smsService;

    public function __construct(FirebaseService $firebaseService, SmsService $smsService)
    {
        $this->firebaseService = $firebaseService;
        $this->smsService = $smsService;
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string|regex:/^[0-9]{10}$/',
        ]);

        // Use SMS service instead of Firebase
        $result = $this->smsService->sendOtp($request->mobile);
        
        return response()->json($result);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string|regex:/^[0-9]{10}$/',
            'otp' => 'required|string|size:6',
        ]);

        // Use SMS service instead of Firebase
        $result = $this->smsService->verifyOtp($request->mobile, $request->otp);
        
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
            'country_code' => 'required|string|regex:/^\+[0-9]{1,4}$/',
            'mobile' => 'required|string|regex:/^[0-9]{7,15}$/|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])/'
            ],
            'role' => 'nullable|in:admin,student',
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&#)',
        ]);

        // Validate mobile number format based on country code
        $this->validateMobileByCountry($request->country_code, $request->mobile);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'country_code' => $request->country_code,
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'student', // Default to student
            'mobile_verified_at' => now(), // Auto-verify since OTP is disabled
        ]);

        return response()->json([
            'message' => 'Registration successful. Please login.',
            'user' => $user,
        ], 201);
    }

    public function checkAvailability(Request $request)
    {
        $request->validate([
            'field' => 'required|in:email,mobile',
            'value' => 'required|string',
        ]);

        $field = $request->field;
        $value = $request->value;

        $exists = User::where($field, $value)->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists 
                ? ucfirst($field) . ' is already registered.' 
                : ucfirst($field) . ' is available.'
        ]);
    }

    private function validateMobileByCountry($countryCode, $mobile)
    {
        $patterns = [
            '+91' => ['pattern' => '/^[6-9][0-9]{9}$/', 'name' => 'India', 'length' => 10],
            '+1' => ['pattern' => '/^[2-9][0-9]{9}$/', 'name' => 'United States', 'length' => 10],
            '+44' => ['pattern' => '/^[1-9][0-9]{9,10}$/', 'name' => 'United Kingdom', 'length' => 10],
            '+61' => ['pattern' => '/^[4-5][0-9]{8}$/', 'name' => 'Australia', 'length' => 9],
            '+971' => ['pattern' => '/^[5][0-9]{8}$/', 'name' => 'UAE', 'length' => 9],
            '+65' => ['pattern' => '/^[8-9][0-9]{7}$/', 'name' => 'Singapore', 'length' => 8],
            '+60' => ['pattern' => '/^[1][0-9]{8,9}$/', 'name' => 'Malaysia', 'length' => 9],
            '+86' => ['pattern' => '/^[1][0-9]{10}$/', 'name' => 'China', 'length' => 11],
            '+81' => ['pattern' => '/^[7-9][0-9]{9}$/', 'name' => 'Japan', 'length' => 10],
            '+82' => ['pattern' => '/^[1][0-9]{9}$/', 'name' => 'South Korea', 'length' => 10],
            '+49' => ['pattern' => '/^[1][0-9]{9,10}$/', 'name' => 'Germany', 'length' => 10],
            '+33' => ['pattern' => '/^[6-7][0-9]{8}$/', 'name' => 'France', 'length' => 9],
            '+39' => ['pattern' => '/^[3][0-9]{8,9}$/', 'name' => 'Italy', 'length' => 9],
            '+34' => ['pattern' => '/^[6-7][0-9]{8}$/', 'name' => 'Spain', 'length' => 9],
            '+7' => ['pattern' => '/^[9][0-9]{9}$/', 'name' => 'Russia', 'length' => 10],
            '+55' => ['pattern' => '/^[1-9][0-9]{10}$/', 'name' => 'Brazil', 'length' => 11],
            '+52' => ['pattern' => '/^[1-9][0-9]{9}$/', 'name' => 'Mexico', 'length' => 10],
            '+27' => ['pattern' => '/^[6-8][0-9]{8}$/', 'name' => 'South Africa', 'length' => 9],
            '+234' => ['pattern' => '/^[7-9][0-9]{9}$/', 'name' => 'Nigeria', 'length' => 10],
            '+20' => ['pattern' => '/^[1][0-9]{9}$/', 'name' => 'Egypt', 'length' => 10],
        ];

        if (!isset($patterns[$countryCode])) {
            throw ValidationException::withMessages([
                'country_code' => ['Invalid country code.'],
            ]);
        }

        $pattern = $patterns[$countryCode];
        if (!preg_match($pattern['pattern'], $mobile)) {
            throw ValidationException::withMessages([
                'mobile' => ["Please enter a valid {$pattern['name']} mobile number ({$pattern['length']} digits)."],
            ]);
        }
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
            $result = $this->smsService->verifyOtp($request->mobile, $request->otp, true);
            
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