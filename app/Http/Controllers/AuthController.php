<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\User;
use App\Tag;
use App\Notifications\SignupActivate;
use Avatar;
use Storage;

class AuthController extends Controller
{
    public function signupActivate($token)
    {
        $user = User::where('activation_token', $token)->first();
        if (!$user) {
            return response()->json([
                'message' => 'This activation token is invalid.'
            ], 404);
        }
        $user->active = true;
        $user->activation_token = '';
        $user->email_verified_at = Carbon::parse(Carbon::now())->toDateTimeString();
        $user->save();
        $avatar = Avatar::create($user->name)->getImageObject()->encode('png');
        Storage::put('avatars/'.$user->id.'/avatar.png', (string) $avatar);
        return response('<h1>Votre compte est activer, Merci</h1>',200);
    }
    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function signup(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed',
            'specialiste' => 'required|boolean'
        ]);
        $user_specialiste = $request->specialiste;

        if($user_specialiste){
            $request->validate([
                'salaire' => 'required',
                'tel' => 'required'
            ]);
            $user = new User([
                'name' => $request->name,
                'email' => $request->email,
                'tel' => $request->tel,
                'password' => bcrypt($request->password),
                'specialiste' => true,
                'salaire' => $request->salaire,
                'excellence' => 0,
                'activation_token' => str_random(60)
            ]);
            $user->save();
            try {
                $user->notify(new SignupActivate($user));
            } catch (\Throwable $th) {
                $user->delete();
                return response()->json([
                    'message' => 'Service Unavailable'
                ], 503);
            }
        }else{
            $user = new User([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'specialiste' => false,
                'activation_token' => str_random(60)
            ]);
            $user->save();
            try {
                $user->notify(new SignupActivate($user));
            } catch (\Throwable $th) {
                $user->delete();
                return response()->json([
                    'message' => 'Service Unavailable'
                ], 503);
            }
        }
        return response()->json([
            'message' => 'Verifier votre boite email pour l\'activation',
            'user' => $user
        ], 201);
    }
  
    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);
        $credentials = request(['email', 'password']);
        $credentials['active'] = 1;
        $credentials['deleted_at'] = null;
        if(!Auth::attempt($credentials))
            return response()->json([
                'message' => 'Valeurs incorrectes!'
            ], 401);
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        $token->expires_at = Carbon::now()->addWeeks(4);
        $token->save();
        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'user' => $user,
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
            ],200);
    }
  
    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Au revoir!!'
        ],200);
    }
  
    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function update(Request $request){
        $user = User::find($request->user);
        if($user->specialiste){
            if(isset($request->name)) $user->name = $request->name;
            if(isset($request->tel)) $user->tel = $request->tel;
            if(isset($request->password)) $user->password = bcrypt($request->password);
            if(isset($request->salaire)) $user->salaire = $request->salaire;
            if(isset($request->tags_id)){
                $user->tags()->detach();
                $tags_id = $request->tags_id;
                if(is_array ( $tags_id )){
                    foreach ($tags_id as $value) {
                        $user->tags()->attach($value);
                    }
                }else{
                    $user->tags()->attach($tags_id);
                }
            }
        }else{
            if(isset($request->name)) $user->name = $request->name;
            if(isset($request->password)) $user->password = bcrypt($request->password);
        }
        $user->save();
        return response()->json([
            'message' => 'User modifier avec succes!',
            'data' => $user
        ], 200);
    }
}
