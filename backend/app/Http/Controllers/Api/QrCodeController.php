<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EncryptController;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

class QrCodeController extends Controller
{
    /**
     * Generate an encrypted string from a number for QR code generation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate(Request $request)
    {
        $ip = $request->ip();
        if ($ip !== '192.168.0.1') {
            return response()->json(['error' => 'Access denied.'.  $ip], 403);
        }
        $validator = Validator::make($request->all(), [
            'number' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $encryptController = new EncryptController();
        $encryptedString = $encryptController->customerEncrypt($request->input('number'));

        return response()->json(['encrypted_data' => $encryptedString]);
    }
}