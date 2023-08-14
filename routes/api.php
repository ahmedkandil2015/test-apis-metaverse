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
    //update user profile
    Route::put('/user/profile', function (Request $request) {
        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => ['required'],
            'photo' => 'string',
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
        //save image from baswe64 to storage
        if ($request->photo) {
            $image = $request->photo;
            $name = time() . '.' . explode('/', explode(':', substr($image, 0, strpos($image, ';')))[1])[1];
            \Image::make($request->photo)->save(public_path('storage/profiles/') . $name);
            $request->merge(['profile_photo_path' => $name]);
        }
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'profile_photo_path' => $request->profile_photo_path,
        ]);
        return response()->json(['message' => 'profile updated successfully']);
    });

    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'logout successfully']);
    });
});
