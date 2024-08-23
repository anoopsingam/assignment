<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;


/**
 * Class ApiAuthController :  The controller for handling API authentication.
 */
class ApiAuthController extends Controller
{

    /**
     * @param string $loginUrl
     */
    public function __construct(
        protected string $loginUrl="",
    )
    {
        $this->loginUrl = config('services.debricked.url').'/login_check';
    }




    /**
     * Handle the login request.
     *
     * @param \App\Http\Requests\LoginRequest $request The login request containing user credentials.
     * @return \Illuminate\Http\JsonResponse The JSON response containing the authentication token or an error message.
     */
    public function login(LoginRequest $request)
    {
        // Validate the request
        $validated = $request->validated();

        try {
            // Authenticate the user
            $auth = $this->Authenticate($validated['email'], $validated['password']);

            // Check if the authentication was successful
            if ($auth) {
                // Return the token
                (new DocumentController())->getSupportedFileFormats();


                return response()->json(['status' => true, 'token' => $auth->token , 'message' => 'User Authenticated']);
            } else {
                // Return error message
                return response()->json(['status' => false, 'message' => 'Invalid Credentials'], 401);
            }
        } catch (\Exception | GuzzleException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 401);
        }
    }

    /**
     * Authenticate the user with the provided email and password.
     *
     * @param string $email The user's email address.
     * @param string $password The user's password.
     * @return object|null The authentication token object if successful, null otherwise.
     * @throws \Exception|\GuzzleHttp\Exception\GuzzleException If there is an error during authentication.
     */
    private function Authenticate(string $email, string $password): ?object
    {
        try {
            // Create a new Guzzle HTTP client
            $auth = new Client();

            // Send a POST request to the login URL with the email and password
            $response = $auth->post($this->loginUrl, [
                'form_params' => [
                    '_username' => $email,
                    '_password' => $password
                ]
            ]);

            // Check if the request was successful
            if ($response->getStatusCode() == 200) {


                // Return the token object
                return json_decode($response->getBody()->getContents());
            } else {
                // Throw an exception if the authentication failed
                throw new \Exception("Error Authenticating User with the API");
            }
        } catch (\Exception $e) {
            // Throw an exception if there is an error during the request
            $statusCode = $e->getCode();
            $response = $e->getResponse();
            $message = $response ? json_decode($response->getBody()->getContents())->message : $e->getMessage();
            throw new \Exception("Error Authenticating User with the API: $statusCode - $message");
        }
    }


}
