<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{

    public function login(Request $request)
    {
        $login = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);
 
        if (isset($request->token)) {
            return $this->googleLogin($request->token);
        }

        $login_result = Auth::attempt(['email' => $request->email, 'password' => $request->password]);

        if (!$login_result) {
            return response()->json(array(
                'error' => 'invalid login credentials',
            ), 401);
        }
        
        $user = Auth::user();

        if (is_null($user->email_verified_at)) {
            Auth::logout();
            return response()->json(array(
                'error' => 'Your user email is not yet verified',
            ), 401);
        }
        $token = $user->createToken('authToken')->accessToken;

        return response()->json(array(
            'user' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'category' => $user->category,
                'email' => $user->email,
                'entity' => $user->entity,
            ],
            'access_token' => $token,
        ), 200);
    }

    private function googleLogin($authToken)
    {
        // GOOGLE LOGIN
        if ((strlen($authToken) > 8)) {
            // fetch the basic info details
            $endpoint = 'https://www.googleapis.com/oauth2/v3/tokeninfo';
            $client = new \GuzzleHttp\Client();

            $response = $client->request('GET', $endpoint, [
                'query' => [
                    'id_token' => $authToken,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $resp = json_decode($response->getBody());

            // just a fall back for errors
            if (isset($resp->given_name)) {

                $firstName = $resp->given_name;
                $lastName = $resp->family_name;
                $email = $resp->email;
                $avatar = $resp->picture;

                $user_valid = DB::table('users')->where('email', $email)->first();

                if ($user_valid) {

                    Auth::loginUsingId($user_valid->id);

                    $user = Auth::user();
                    if (is_null($user->email_verified_at)) {
                        Auth::logout();
                        return response()->json(array(
                            'error' => 'Your user email is not yet verified',
                        ), 401);
                    }

                    $token = $user->createToken('authToken')->accessToken;

                    return response()->json([
                        'user' => [
                            'name' => $user->name,
                            'category' => $user->category,
                            'email' => $user->email,
                            'church' => $user->church,
                        ],
                        'access_token' => $token,
                    ], 200);
                } else {
                    return response()->json(array(
                        'error' => 'Cannot use Google to authenticate. Sign up first',
                    ), 401);
                }
            } else {
                return response()->json(array(
                    'error' => 'A server side error occured. Cannot use Google login right now.',
                ), 500);
            }
        } else {
            return response()->json(array(
                'error' => 'The token provided is too short',
            ), 401);
        }
    }
}
