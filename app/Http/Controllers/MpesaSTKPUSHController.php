<?php

namespace App\Http\Controllers;

use App\Models\MpesaSTK;
use Iankumu\Mpesa\Facades\Mpesa; // import the Facade
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaSTKPUSHController extends Controller
{
    public $result_code = 1;
    public $result_desc = 'An error occurred';

    // Initiate STK Push Request
    public function STKPush(Request $request)
    {
        $amount = $request->input('amount');
        $phoneno = $request->input('phonenumber');
        $account_number = $request->input('account_number');

        $response = Mpesa::stkpush($phoneno, $amount, $account_number);
        
        /** @var \Illuminate\Http\Client\Response $response */
        $result = $response->json(); 


        if (isset($result['MerchantRequestID']) && isset($result['CheckoutRequestID'])) {
            MpesaSTK::create([
                'merchant_request_id' => $result['MerchantRequestID'],
                'checkout_request_id' => $result['CheckoutRequestID']
            ]);
            return response()->json(['success' => true, 'result' => $result]);
        } else {
            Log::error('Mpesa STK Push Error: Missing expected keys in the response', $result);
            return response()->json(['success' => false, 'message' => 'Failed to initiate STK Push. Please try again.']);
        }
    }



    // This function is used to review the response from Safaricom once a transaction is complete
    public function STKConfirm(Request $request)
    {
        $stk_push_confirm = (new STKPush())->confirm($request);

        if ($stk_push_confirm) {

            $this->result_code = 0;
            $this->result_desc = 'Success';
        }
        return response()->json([
            'ResultCode' => $this->result_code,
            'ResultDesc' => $this->result_desc
        ]);
    }
}