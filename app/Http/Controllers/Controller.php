<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\nvLead;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function interakt_wa_msg_send(int $phone_no, string $name, string $message, string $template_type) {
        if(env('INTERAKT_STATUS') !== true){
            return response()->json(['success' => false, 'alert_type' => 'error', 'message' => "Interakt is disabled."]);
        }
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://api.interakt.ai/v1/public/message/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([
                    "countryCode" => "+91",
                    "phoneNumber" => $phone_no,
                    "callbackData" => "Send successfully.",
                    "type" => "Template",
                    "template" => [
                        "name" => $template_type,
                        "languageCode" => "en",
                        "headerValues" => [$name],
                        "bodyValues" => [$message],
                    ],
                ]),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic " . env('INTERAKT_KEY'),
                    'Content-Type: application/json',
                    'Cookie: ApplicationGatewayAffinity=a8f6ae06c0b3046487ae2c0ab287e175; ApplicationGatewayAffinityCORS=a8f6ae06c0b3046487ae2c0ab287e175'
                ),
            )
        );
        $response = json_decode(curl_exec($curl));
        curl_close($curl);
        return $response;
    }

    function notify_vendor_lead_using_interakt($phone, $name, $message, $lead_id) {
        if(env('INTERAKT_STATUS') !== true){
            return response()->json(['success' => false, 'alert_type' => 'error', 'message' => "Interakt is disabled."]);
        }
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://api.interakt.ai/v1/public/message/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_POSTFIELDS => '{
					"countryCode": "+91",
					"phoneNumber": "' . $phone . '",
					"callbackData": "Send SuccesSuccessfully",
					"type": "Template",
					"template": {
						"name": "notify_vendor_lead",
						"languageCode": "en",
						"headerValues":[
							"' . $name . '"
						],
						"bodyValues": [
							"' . $message . '"
						],
						"buttonValues": {
							"0" : ["' . $lead_id . '"]
						}
					}
				}',
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic " . env('INTERAKT_KEY'),
                    'Content-Type: application/json',
                    'Cookie: ApplicationGatewayAffinity=a8f6ae06c0b3046487ae2c0ab287e175; ApplicationGatewayAffinityCORS=a8f6ae06c0b3046487ae2c0ab287e175'
                ),
            )
        );
        $response = json_decode(curl_exec($curl));
        curl_close($curl);
        return $response;
    }
    // function notify_wbvendor_lead_using_interakt($phone, $name, $number, $eventdate, $pax, $lead_id) {
    // if(env('INTERAKT_STATUS') === false){
    //     return response()->json(['success' => false, 'alert_type' => 'error', 'message' => "Interakt is disabled."]);
    // }
    //     $curl = curl_init();
    //     curl_setopt_array(
    //         $curl,
    //         array(
    //             CURLOPT_URL => 'https://api.interakt.ai/v1/public/message/',
    //             CURLOPT_RETURNTRANSFER => true,
    //             CURLOPT_ENCODING => '',
    //             CURLOPT_MAXREDIRS => 10,
    //             CURLOPT_TIMEOUT => 0,
    //             CURLOPT_FOLLOWLOCATION => true,
    //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //             CURLOPT_CUSTOMREQUEST => 'POST',
    //             CURLOPT_POSTFIELDS => '{
	// 				"countryCode": "+91",
	// 				"phoneNumber": "' . $phone . '",
	// 				"callbackData": "Send SuccesSuccessfully",
	// 				"type": "Template",
	// 				"template": {
	// 					"name": "notify_vendor_lead",
	// 					"languageCode": "en",
	// 					"headerValues":[
	// 						"' . $name . '"
	// 					],
	// 					"bodyValues": [
	// 						"' . $number . ',' . $eventdate . ',' . $pax . '"
	// 					],
	// 					"buttonValues": {
	// 						"0" : ["' . $lead_id . '"]
	// 					}
	// 				}
	// 			}',
    //             CURLOPT_HTTPHEADER => array(
    //                 "Authorization: Basic " . env('INTERAKT_KEY'),
    //                 'Content-Type: application/json',
    //                 'Cookie: ApplicationGatewayAffinity=a8f6ae06c0b3046487ae2c0ab287e175; ApplicationGatewayAffinityCORS=a8f6ae06c0b3046487ae2c0ab287e175'
    //             ),
    //         )
    //     );
    //     $response = json_decode(curl_exec($curl));
    //     curl_close($curl);
    //     return $response;
    // }

    function notify_wbvendor_lead_using_interakt($phone, $name, $number, $eventdate, $pax, $lead_id) {
        if(env('INTERAKT_STATUS') !== true){
            return response()->json(['success' => false, 'alert_type' => 'error', 'message' => "Interakt is disabled."]);
        }
        $curl = curl_init();

        $payload = [
            "countryCode" => "+91",
            "phoneNumber" => $phone,
            "callbackData" => "Send SuccesSuccessfully",
            "type" => "Template",
            "template" => [
                "name" => "wb_venue_alert",
                "languageCode" => "en",
                "headerValues" => [$name],
                "bodyValues" => ["$number,$eventdate,$pax"],
                "buttonValues" => [
                    "0" => [$lead_id]
                ]
            ]
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.interakt.ai/v1/public/message/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic " . env('INTERAKT_KEY'),
                'Content-Type: application/json',
                'Cookie: ApplicationGatewayAffinity=a8f6ae06c0b3046487ae2c0ab287e175; ApplicationGatewayAffinityCORS=a8f6ae06c0b3046487ae2c0ab287e175'
            ),
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            return "cURL Error: " . $error;
        }
        return json_decode($response, true);

    }


    public function validate_venue_lead_phone_number($phone_number) {
        $pattern = "/^\d{10}$/";
        try {
            if (preg_match($pattern, $phone_number)) {
                $lead = Lead::where('mobile', $phone_number)->first();
                if (!$lead) { // lead should not exist
                    return response()->json(['success' => true, 'alert_type' => 'success', 'message' => "Phone number validated."]);
                } else {
                    return response()->json(['success' => false, 'alert_type' => 'error', 'message' => "Lead is already exist with this phone number, Contact to your manager or admin."]);
                }
            } else {
                return response()->json(['success' => false, 'alert_type' => 'error', 'message' => "Invalid phone number."]);
            }
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'alert_type' => 'error', 'message' => "Something went wrong."]);
        }
    }
    public function validate_nonvenue_lead_phone_number($phone_number) {
        $pattern = "/^\d{10}$/";
        try {
            if (preg_match($pattern, $phone_number)) {
                $lead = nvLead::where('mobile', $phone_number)->first();
                if (!$lead) {
                    return response()->json(['success' => true, 'alert_type' => 'success', 'message' => "Phone number validated."]);
                } else {
                    return response()->json(['success' => false, 'alert_type' => 'error', 'message' => "Lead is already exist with this phone number, Contact to your manager or admin."]);
                }
            } else {
                return response()->json(['success' => false, 'alert_type' => 'error', 'message' => "Invalid phone number."]);
            }
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'alert_type' => 'error', 'message' => "Something went wrong."]);
        }
    }

    public function getCalender() {
        $calendar = [];
        for ($i = 0; $i <= 12; $i++) {
            if ($i == 0) {
                array_push($calendar, Carbon::today()->setDay(1)->addMonth(-1));
            } elseif ($i == 1) {
                array_push($calendar, Carbon::today()->startOfMonth());
            } else {
                array_push($calendar, Carbon::today()->setDay(1)->addMonth($i - 1));
            }
        }
        return $calendar;
    }
}
