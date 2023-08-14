<?php

use App\Http\Controllers\PostController;
use App\Models\Post;
use App\Models\User;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::apiResource('/posts', PostController::class);

Route::post('/tokens/create', function (Request $request) {
    $data = Validator::make($request->all(), [
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);
    if ($data->fails()) {
        return response()->json(['error' => $data->errors()], 401);
    }

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    $token = $user->createToken('new token')->plainTextToken;

    return response()->json([
        'token' => $token,
    ]);
});
//create group and protected routes with sanctum
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/user/prfile', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'logout successfully']);
    });
});
