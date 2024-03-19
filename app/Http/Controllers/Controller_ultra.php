<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\nvLead;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function interakt_wa_msg_send(int $phone_no, string $name, string $message) {
        $token = env("ULTRAMSG_API_TOKEN");
            $instance_id = env("ULTRAMSG_API_INSTANCE");
        $params=array(
            'token' => $token,
            'to' => "91$phone_no",
            'body' => "*Hi $name,*\nWe are excited to have you on board!\n\nUse OTP *$message* log in.\n\nKindly enter the OTP to proceed.\n\nPlease note, that this OTP is valid for the next 10 minutes and can be used only once. For optimum security, please don’t share your OTP with anyone.\n\nThank You,\n*Team Wedding Banquets*"
            );
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => "https://api.ultramsg.com/$instance_id/messages/chat",
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_SSL_VERIFYHOST => 0,
              CURLOPT_SSL_VERIFYPEER => 0,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_POSTFIELDS => http_build_query($params),
              CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded"
              ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
        return $response;
    }

    function notify_vendor_lead_using_interakt($phone, $name, $message, $lead_id) {
        $token = env("ULTRAMSG_API_TOKEN");
            $instance_id = env("ULTRAMSG_API_INSTANCE");
        $params=array(
            'token' => $token,
            'to' => "91$phone",
            'body' => "*Hi $name*\nYou have received a new enquiry from $message have booked with *Wedding Banquets.*\n\nFor any Query:-\nDon’t hesitate to get in touch with our *RM* Team: 18008890082\n\n*Respond to leads on the go with the Wedding Banquets for Business CRM*\n\n_Respond within 2 hours for the best chance of booking._\n\n*Open:-* https://wbcrm.in/vendor/leads/view/$lead_id"
            );
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => "https://api.ultramsg.com/$instance_id/messages/chat",
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_SSL_VERIFYHOST => 0,
              CURLOPT_SSL_VERIFYPEER => 0,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_POSTFIELDS => http_build_query($params),
              CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded"
              ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
        return $response;
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
