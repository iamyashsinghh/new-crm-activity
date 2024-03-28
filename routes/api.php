<?php

use App\Models\CrmMeta;
use App\Models\Lead;
use App\Models\nvLead;
use App\Models\nvrmLeadForward;
use App\Models\LeadForward;
use App\Models\BlockedIp;
use App\Models\whatsappMessages;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Illuminate\Support\Str;
use App\Models\Vendor;




/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

function getAssigningRm() {
    DB::beginTransaction();
    try {
        $firstRmWithIsNext = TeamMember::where(['role_id' => 4, 'status' => 1, 'is_next' => 1])
                                       ->orderBy('id', 'asc')
                                       ->first();
        if ($firstRmWithIsNext) {
            TeamMember::where(['role_id' => 4, 'status' => 1])
                      ->where('id', '!=', $firstRmWithIsNext->id)
                      ->update(['is_next' => 0]);
        }
        if (!$firstRmWithIsNext) {
            $firstRmWithIsNext = TeamMember::where(['role_id' => 4, 'status' => 1])
                                           ->orderBy('id', 'asc')
                                           ->first();
            if (!$firstRmWithIsNext) {
                throw new Exception('No RM available');
            }
            $firstRmWithIsNext->is_next = 1;
            $firstRmWithIsNext->save();
            DB::commit();
            return $firstRmWithIsNext;
        } else {
            $firstRmWithIsNext->is_next = 0;
            $firstRmWithIsNext->save();
            $nextRm = TeamMember::where(['role_id' => 4, 'status' => 1])
                                ->where('id', '>', $firstRmWithIsNext->id)
                                ->orderBy('id', 'asc')
                                ->first();

            if (!$nextRm) {
                $nextRm = TeamMember::where(['role_id' => 4, 'status' => 1])
                                    ->orderBy('id', 'asc')
                                    ->first();
            }
            $nextRm->is_next = 1;
            $nextRm->save();
        }
        DB::commit();
        return $firstRmWithIsNext;
    } catch (Exception $e) {
        \Log::error($e->getMessage());
        DB::rollback();
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


function assignLeadsToRMs() {
    set_time_limit(300);
    $leads = Lead::select('lead_id')->whereNull('deleted_at')->get();
    DB::beginTransaction();
    try {
        foreach ($leads as $lead) {
            $get_rm = getAssigningRm();
            if (!$get_rm) {
                throw new Exception('No RM available for assignment.');
            }
                Lead::where('lead_id', $lead->lead_id)->update([
                'assign_to' => $get_rm->name,
                'assign_id' => $get_rm->id,
            ]);
        }
        DB::commit();
        return "Successfully assigned leads to RMs.";
    } catch (Exception $e) {
        DB::rollback();
        Log::error('Failed to assign leads to RMs: ' . $e->getMessage());
        return "Error during lead assignment: " . $e->getMessage();
    }
}
// Route::get('/assign_leads_to_rms', function () {
//     return assignLeadsToRMs();
// });

Route::post('/save_wa', function (Request $request) {
    $name = $request['contacts'][0]['profile']['name'];
    $number = $request['messages']['from'];
    $timestamp = $request['messages']['timestamp'];
    $type = $request['messages']['type'];
    $textMsg = '';
    $id = "";
    $current_timestamp = date('Y-m-d H:i:s', $timestamp);
    if (strlen($number) > 10) {
        if (substr($number, 0, 2) == '91') {
         $number = substr($number, 2);
      }
     }
    $newWaMsg = new whatsappMessages();
    $newWaMsg->msg_id = $request['id'];
    $newWaMsg->msg_from = $number;
    $newWaMsg->time = $current_timestamp;
    $newWaMsg->type = $type;
    $newWaMsg->is_sent = "0";
    if ($type == 'text') {
        $textMsg = $request['messages'][$type]['body'];
        $newWaMsg->body = $textMsg;
    } elseif ($type == 'document') {
        $id = $request['messages'][$type]['id'];
        $newWaMsg->doc = $id;
    } elseif ($type == "audio") {
        $id = $request['messages'][$type]['id'];
        $newWaMsg->doc = $id;
    } elseif ($type == "video") {
        $id = $request['messages'][$type]['id'];
        $newWaMsg->doc = $id;
    } elseif ($type == "image") {
      $id = $request['messages'][$type]['id'];
        $newWaMsg->doc = $id;
    } elseif ($type == "button") {
      $textMsg = $request['messages'][$type]['text'];
        $newWaMsg->body = $textMsg;
    } elseif ($type == "contacts") {
        $contact_name = $request['messages'][$type][0]['name']['formatted_name'];
        $contact_number = $request['messages'][$type][0]['phones'][0]['phone'];
        $data = json_encode([
            'name' => $contact_name,
            'mobile' => $contact_number,
        ]);
        $newWaMsg->doc = $data;
    } elseif ($type == "location") {
        $latitude = $request['messages'][$type]['latitude'];
        $longitude = $request['messages'][$type]['longitude'];
        $locationurl = "https://www.google.com/maps/@$latitude,$longitude,12z";
        $newWaMsg->doc = $locationurl;
    } else {
        Log::info("error saving whatsapp msg");
    }
    $newmsg_saved = $newWaMsg->save();
    $getlead = Lead::where('mobile', $number)->first();
    $getlead2 = nvrmLeadForward::where('mobile', $number)->first();
    $getlead3 = nvLead::where('mobile', $number)->first();
    $getlead4 = Vendor::where('mobile', $number)->first();
    $getlead5 = Vendor::where('alt_mobile_number', $number)->first();
    if ($getlead) {
        $getlead->is_whatsapp_msg = 1;
        $getlead->whatsapp_msg_time = $current_timestamp;
        $getlead->save();
    }
    if ($getlead2) {
        $getlead2->is_whatsapp_msg = 1;
        $getlead2->whatsapp_msg_time = $current_timestamp;
        $getlead2->save();
    }
    if ($getlead3) {
        $getlead3->is_whatsapp_msg = 1;
        $getlead3->save();
    }
        if ($getlead4) {
            $getlead4->is_whatsapp_msg = 1;
            $getlead4->whatsapp_msg_time = $current_timestamp;
            $getlead4->save();
        }

        if ($getlead5) {
            $getlead5->is_whatsapp_msg = 1;
            $getlead5->whatsapp_msg_time = $current_timestamp;
            $getlead5->save();
        }

    if (!$getlead && !$getlead2 && !$getlead3 && !$getlead4 && !$getlead5) {
        $current_timestamp = date('Y-m-d H:i:s');
            $lead = new Lead();
            $lead->name = $name;
            $lead->email = 'wbdelhileads@gmail.com';
            $lead->mobile = $number;
            $lead->lead_datetime = $current_timestamp;
            $lead->source = "WB|WhatsApp";
            $lead->preference = 'whatsapp';
            $lead->locality = null;
            $lead->lead_status = "Super Hot Lead";
            $lead->read_status = false;
            $lead->service_status = false;
            $lead->done_title = null;
            $lead->done_message = null;
            $lead->lead_color = "#4bff0033";
            $lead->virtual_number = null;
            $lead->is_whatsapp_msg = 1;
            $lead->whatsapp_msg_time = $current_timestamp;
            $get_rm = getAssigningRm();
            $lead->assign_to = $get_rm->name;
            $lead->assign_id = $get_rm->id;
            $lead->save();
        }

    if($newmsg_saved){
        return response()->json([
            'success' => true,
            'message' => 'Message saved successfully',
        ], 200);
    } else {
        Log::info("error in saving new what");
        return response()->json([
            'success' => false,
            'message' => 'Failed to save the message',
        ], 500);
    }
});

// Route::post('/save_wa', function (Request $request) {
//     Log::info($request);
// });

Route::get('/manupulate_csrf', function (){
    $randomValue = Str::random(10);
    DB::connection('mysql')->table('randomnes')->updateOrInsert(
        ['id' => 1],
        ['random_pass' => $randomValue]
    );
});

Route::get('/csrf-token', function () {
    $randompassvalue = DB::connection('mysql')->table('randomnes')->where('id', 1)->value('random_pass');
    $yash = md5(sha1(md5($randompassvalue)));
    $randomnum = rand(74365874, 74365874);
    $randomnum2 = rand(1, 19);
    $maincode = "$randomnum2-546674$yash@$randomnum";
    $out = base64_encode(base64_encode($maincode));
    return json_encode(['csrfToken' => $out]);

});

function simpleDecrypt($encoded) {
    $encoded = base64_decode($encoded);
    $decoded = "";
    for ($i = 0; $i < strlen($encoded); $i++) {
        $b = ord($encoded[$i]);
        $a = $b ^ 10;
        $decoded .= chr($a);
    }
    return base64_decode(base64_decode($decoded));
}

function notify_users_about_lead_interakt_async($mobile ,$name) {
    if(env('INTERAKT_STATUS') !== true){
        return response()->json(['success' => false, 'alert_type' => 'error', 'message' => "Interakt is disabled."]);
    }
    $client = new Client();

    if (empty($name)) {
        $name = "Sir/Mam";
    }

    return $client->postAsync('https://api.interakt.ai/v1/public/message/', [
        'json' => [
            'countryCode' => '+91',
            'phoneNumber' => $mobile,
            'callbackData' => 'Send Successfully',
            'type' => 'Template',
            'template' => [
                'name' => 'notify_users_uer_leads_1n',
                'languageCode' => 'en',
                 "headerValues" => [$name],
            ]
        ],
        'headers' => [
            'Authorization' => 'Basic ' . env('INTERAKT_KEY'),
            'Content-Type' => 'application/json',
            'Cookie' => 'ApplicationGatewayAffinity=a8f6ae06c0b3046487ae2c0ab287e175; ApplicationGatewayAffinityCORS=a8f6ae06c0b3046487ae2c0ab287e175'
        ]
    ]);
}

Route::post('/leads', function (Request $request) {
     if($request->ip() == '14.97.20.4'){
            try {
        $is_name_valid = $request->post('name') != null ? "required|string|max:255" : "";
        $is_email_valid = $request->post('email') != null ? "required|email" : "";
        $is_preference_valid = $request->post('preference') != null ? "required|string|max:255" : "";
        $mobile = $request->post('mobile');
        $validate = Validator::make($request->all(), [
            'name' => $is_name_valid,
            'email' => $is_email_valid,
            'preference' => $is_preference_valid,
        ]);
        if ($validate->fails()) {
            return response()->json(['status' => false, 'msg' => $validate->errors()->first()]);
        }
        $mobile = $request->post('mobile') ?: $request->post('caller_id_number');
        $pattern = "/^\d{10}$/";
        if (!preg_match($pattern, $mobile)) {
            return response()->json(['status' => false, 'msg' => "Invalid mobile number."]);
        }
        $current_timestamp = date('Y-m-d H:i:s');
        if ($request->post('call_to_number')) {
            $call_to_wb_api_virtual_number = $request->post('call_to_number');
            $lead_source = "WB|Call";
            $crm_meta = CrmMeta::find(1);
            $preference = $crm_meta->meta_value;
        } else {
            $call_to_wb_api_virtual_number = null;
            $lead_source = "WB|Call";
            $preference = $request->post('preference');
        }
        $listing_data = DB::connection('mysql2')->table('venues')->where('slug', $preference)->first();
        if (!$listing_data) {
            $listing_data = DB::connection('mysql2')->table('vendors')->where('slug', $preference)->first();
        }
        $locality = DB::connection('mysql2')->table('locations')->where('id', $listing_data ? $listing_data->location_id : 0)->first();
        $lead = Lead::where('mobile', $mobile)->first();
        if ($lead) {
            $lead->enquiry_count = $lead->enquiry_count + 1;
        } else {
            $lead = new Lead();
            $lead->name = $request->post('name');
            $lead->email = $request->post('email');
            $lead->mobile = $mobile;
        }
        $lead->lead_datetime = $current_timestamp;
        $lead->source = $lead_source;
        $lead->preference = $preference;
        $lead->locality = $locality ? $locality->name : null;
        $lead->lead_status = "Super Hot Lead";
        $lead->read_status = false;
        $lead->service_status = false;
        $lead->done_title = null;
        $lead->done_message = null;
        $lead->lead_color = "#4bff0033"; //green color
        $lead->virtual_number = $call_to_wb_api_virtual_number;
        $lead->whatsapp_msg_time = $current_timestamp;
        $get_rm = getAssigningRm();
        $lead->assign_to = $get_rm->name;
        $lead->assign_id = $get_rm->id;
        $lead->save();
        $promise = notify_users_about_lead_interakt_async($mobile, $request->post('name'));
            $promise->then(
                function ($response) {
                    Log::info('WhatsApp notification sent successfully');
                },
                function ($exception) {
                    Log::error('Failed to send WhatsApp notification: ' . $exception->getMessage());
                }
            )->wait();
        return response()->json(['status' => true, 'msg' => 'Thank you for contacting us. Our team will reach you soon with best price..!']);
    } catch (\Throwable $th) {
        return response()->json(['status' => false, 'msg' => 'Something went wrong.', 'err' => $th->getMessage()], 500);
    }
}else{
        return response()->json(['status' => false, 'msg' => 'Something went wrong.'], 500);
}
});



Route::post('/new_lead', function (Request $request) {
    $startSubstring = "-546674";
    $endSubstring = "@";
    $encoded = $request->post('token');
    $extractedValue = $encoded;
    $string = base64_decode(base64_decode($extractedValue));
    $startPos = strpos($string, $startSubstring);
    $endPos = strpos($string, $endSubstring, $startPos);
    $finalValue = substr($string, $startPos + strlen($startSubstring), $endPos - $startPos - strlen($startSubstring));
    $randompassvalue = DB::connection('mysql')->table('randomnes')->where('id', 1)->value('random_pass');
    $yash = md5(sha1(md5($randompassvalue)));

if ($finalValue == $yash) {
            try {
        $is_name_valid = $request->post('name') != null ? "required|string|max:255" : "";
        $is_email_valid = $request->post('email') != null ? "required|email" : "";
        $is_preference_valid = $request->post('preference') != null ? "required|string|max:255" : "";
        $mobile = $request->post('mobile');
        $validate = Validator::make($request->all(), [
            'name' => $is_name_valid,
            'email' => $is_email_valid,
            'preference' => $is_preference_valid,
        ]);
        if ($validate->fails()) {
            return response()->json(['status' => false, 'msg' => $validate->errors()->first()]);
        }
        $mobile = $request->post('mobile') ?: $request->post('caller_id_number');
        $pattern = "/^\d{10}$/";
        if (!preg_match($pattern, $mobile)) {
            return response()->json(['status' => false, 'msg' => "Invalid mobile number."]);
        }elseif( $mobile <= 6000000000){
            return response()->json(['status' => false, 'msg' => "Invalid mobile number."]);
        }
        $current_timestamp = date('Y-m-d H:i:s');
        if ($request->post('call_to_number')) {
            $call_to_wb_api_virtual_number = $request->post('call_to_number');
            $lead_source = "WB|Call";
            $crm_meta = CrmMeta::find(1);
            $preference = $crm_meta->meta_value;
        } else {
            $call_to_wb_api_virtual_number = null;
            $lead_source = "WB|Form";
            $preference = $request->post('preference');
        }
        $listing_data = DB::connection('mysql2')->table('venues')->where('slug', $preference)->first();
        if (!$listing_data) {
            $listing_data = DB::connection('mysql2')->table('vendors')->where('slug', $preference)->first();
        }
        $locality = DB::connection('mysql2')->table('locations')->where('id', $listing_data ? $listing_data->location_id : 0)->first();
        $lead = Lead::where('mobile', $mobile)->first();
        if ($lead) {
            $lead->enquiry_count = $lead->enquiry_count + 1;
        } else {
            $lead = new Lead();
            $lead->name = $request->post('name');
            $lead->email = $request->post('email');
            $lead->mobile = $mobile;
        }
        $lead->lead_datetime = $current_timestamp;
        $lead->source = $lead_source;
        $lead->preference = $preference;
        $lead->locality = $locality ? $locality->name : null;
        $lead->lead_status = "Super Hot Lead";
        $lead->read_status = false;
        $lead->service_status = false;
        $lead->done_title = null;
        $lead->done_message = null;
        $lead->whatsapp_msg_time = $current_timestamp;
        $lead->lead_color = "#4bff0033"; //green color
        $lead->virtual_number = $call_to_wb_api_virtual_number;
        $get_rm = getAssigningRm();
        $lead->assign_to = $get_rm->name;
        $lead->assign_id = $get_rm->id;
        $lead->save();
        $promise = notify_users_about_lead_interakt_async($request->post('mobile'), $request->post('name'));
            $promise->then(
                function ($response) {
                    Log::info('WhatsApp notification sent successfully');
                },
                function ($exception) {
                    Log::error('Failed to send WhatsApp notification: ' . $exception->getMessage());
                }
            )->wait();
        return response()->json(['status' => true, 'msg' => 'Thank you for contacting us. Our team will reach you soon with best price..!']);
    } catch (\Throwable $th) {
        return response()->json(['status' => false, 'msg' => 'Something went wrong.', 'err' => $th->getMessage()], 500);
    }
        // }

}else{
        return response()->json(['status' => false, 'msg' => 'Something went wrong.'], 500);
}
});

Route::get('/fetch_leads_from_interakt', function () {
    try {
        $gtDateTime = date('Y-m-d', strtotime('-1 day')) . "T00:00:00.0000";
        $ltDateTime = date('Y-m-d') . "T11:59:00.0000";
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.interakt.ai/v1/public/apis/users/?offset=0&limit=100',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "filters": [
                    {
                        "trait": "created_at_utc",
                        "op": "gt",
                        "val": "' . $gtDateTime . '"
                    },
                    {
                        "trait": "created_at_utc",
                        "op": "lt",
                        "supr_op": "and",
                        "val": "' . $ltDateTime . '"
                    }
                ]
            }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic dDdxNTN5alhyLTItSC1FRVU2RlBBWHpUXzRyc1lFMXMwSGhPYzdQTlRxbzo=',
                'Content-Type: application/json',
                'Cookie: ApplicationGatewayAffinity=a8f6ae06c0b3046487ae2c0ab287e175; ApplicationGatewayAffinityCORS=a8f6ae06c0b3046487ae2c0ab287e175'
            ),
        ));
        $response = json_decode(curl_exec($curl));
        curl_close($curl);
        $daily_customer = $response->data->customers;
        // return $response;

        $current_timestamp = date('Y-m-d H:i:s');

        foreach ($daily_customer as $dc) {
            $name = $dc->traits->name;
            $mobile = $dc->phone_number;

            $lead = Lead::where('mobile', $mobile)->first();
            if ($lead) {
                $lead->enquiry_count = $lead->enquiry_count + 1;
            } else {
                $lead = new Lead();
                $lead->name = $name;
                $lead->mobile = $mobile;
                $lead->source = "WB|Interakt";
            }
            $lead->lead_datetime = $current_timestamp;
            $lead->lead_status = "Super Hot Lead";
            $lead->read_status = false;
            $lead->service_status = false;
            $lead->done_title = null;
            $lead->done_message = null;
            $lead->whatsapp_msg_time = $current_timestamp;
            $lead->lead_color = "#4bff0033";
            $get_rm = getAssigningRm();
            $lead->assign_to = $get_rm->name;
            $lead->assign_id = $get_rm->id;
            $lead->save();
        }
        return response()->json(['success' => true, 'alert_type' => 'success', 'message' => 'Data fetched successfully.']);
    } catch (\Throwable $th) {
        return response()->json(['success' => false, 'alert_type' => 'error', 'message' => 'Somethin went wrong, please try again later.']);
    }
})->name('api.fetchFromInterack');

Route::post('handle_calling_request', function (Request $request) {
    $validate = Validator::make($request->all(), [
        'slug' => 'required|string|max:255',
    ]);
    if ($validate->fails()) {
        return response()->json(['success' => false, 'message' => $validate->errors()->first()]);
    }

    try {
        $crm_meta = CrmMeta::find(1);
        $crm_meta->meta_value = $request->slug;
        $crm_meta->save();
        return response()->json(['success' => true, 'alert_type' => 'success', 'message' => 'Data stored successfully.']);
    } catch (\Throwable $th) {
        return response()->json(['success' => false, 'alert_type' => 'error', 'message' => 'Somethin went wrong, please try again later.']);
    }
});
