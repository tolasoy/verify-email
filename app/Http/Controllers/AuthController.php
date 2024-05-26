<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserVerify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;


class AuthController extends Controller
{
    public function index() {
        return view('auth.login');
    }

    public function registration() {
        return view('auth.registration');
    }

    public function postLogin(Request $request) {
        $request->validate([
            'email' => "required",
            "password" => "required"
        ]);

        $credentials = $request->only(['email', 'password']);
        if (Auth::attempt($credentials)) {
            return redirect()
                ->intended('dashboard')
                ->withSuccess('You have Successfully loggedin');
        }
        return redirect("login")->withSuccess('Oppes! You have entered invalid credentials');
    } 

    public function postRegistration(Request $request) {
        dd($request->all());
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);
        dd($request->all());
        $data = $request->all();
        $createUser = $this->create($data);

        $token = Str::random(64);
        UserVerify::create([
            'user_id' => $createUser->id,
            'token' => $token
        ]);
        Mail::send('email.emailVerificationEmail', ['token' => $token], function($message) use($request) {
            $message->to($request->email);
            $message->subject('Email Verification Mail');
        });
        return 'Great! You have Successfully loggedin';
        // return redirect("dashboard")->withSuccess('Great! You have Successfully loggedin');
    }

    public function dashboard() {
        if (Auth::check()) {
            return view('dashboard');
        }
        return redirect("login")->withSuccess('Opps! You do not have access');
    }

    public function create(array $data) {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password'])
        ]);
    }

    public function logout() {
        Session::flush();
        Auth::logout();
        return Redirect('login');
    }

    public function verifyAccount($token) {
        $verifyUser = UserVerify::where('token', $token)->first();
        $message = 'Sorry your email cannot be identified.';
        if(!is_null($verifyUser) ){
            $user = $verifyUser->user;
               
            if(!$user->is_email_verified) {
                $verifyUser->user->is_email_verified = 1;
                $verifyUser->user->save();
                $message = "Your e-mail is verified. You can now login.";
            } else {
                $message = "Your e-mail is already verified. You can now login.";
            }
        }
      return redirect()->route('login')->with('message', $message);

    }
}
