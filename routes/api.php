<?php


use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\DocumentController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;


Route::middleware('apiAuth')->prefix('v1')->group(function () {

    Route::prefix('auth')->group(function (){
        Route::post('/login', [ApiAuthController::class,'login']);
    });


    Route::prefix('doc')->group(function (){
        Route::get('getSupportedFileFormats',[DocumentController::class,'getSupportedFileFormats']);
        Route::post('uploadFile',[DocumentController::class,'UploadFile']);
        Route::post('queueScan',[DocumentController::class,'queueScan']);
        Route::get('getScanStatus',[DocumentController::class,'getScanStatus']);
    });



    Route::get('test',function (){
        $fileFormats= Cache::get('supported_file_formats');
        return response()->json(['message'=>'API is working','request'=>request()->all(),'fileFormats'=>$fileFormats,'email'=>session()->get('email')]);
    });
});
