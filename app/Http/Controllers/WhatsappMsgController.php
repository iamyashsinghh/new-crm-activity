<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\nvLead;
use App\Models\nvrmLeadForward;
use App\Models\WhatsappBulkMsgTask;
use App\Models\whatsappMessages;
use App\Models\WhatsappCampain;
use App\Models\WhatsappMsgLogs;
use App\Models\WhatsappTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WhatsappMsgController extends Controller
{

    public function ajax_tasks()
    {
        $whatsapp_bulk_msg_task = WhatsappBulkMsgTask::select(
            'whatsapp_bulk_msg_task.id',
            'whatsapp_bulk_msg_task.campaign_name',
            'whatsapp_bulk_msg_task.img',
            'whatsapp_bulk_msg_task.msg',
            'whatsapp_bulk_msg_task.status',
            'whatsapp_bulk_msg_task.created_at',
        )->get();
        return datatables($whatsapp_bulk_msg_task)->toJson();
    }

    public function create_task_by_number(Request $request)
    {
        $WhatsappCampain = WhatsappCampain::select('name', 'template_name')->where('id',$request->input('camp_name'))->first();
        $newtask = new WhatsappBulkMsgTask();
        $newtask->campaign_name = $WhatsappCampain->name;
        $newtask->numbers = $request->input('recipient');
        $WhatsappTemplates = WhatsappTemplates::where('template_name', $WhatsappCampain->template_name)->first();
        $newtask->img = $WhatsappTemplates->img;
        $newtask->msg = $WhatsappTemplates->msg;
        $newtask->template_name = $WhatsappCampain->template_name;
        $newtask->status = "0";
        if ($newtask->save()) {
            return response()->json([
                'success' => true,
                'message' => 'Task saved successfully',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save task',
            ]);
        }
    }

    public function create_task_by_id(Request $request)
    {
        $WhatsappCampain = WhatsappCampain::select('name', 'template_name')->where('id',$request->input('camp_name'))->first();
        $newtask = new WhatsappBulkMsgTask();
        $newtask->campaign_name = $WhatsappCampain->name;
        $newtask->lead_ids = $request->input('recipient');
        $WhatsappTemplates = WhatsappTemplates::where('template_name', $WhatsappCampain->template_name)->first();
        $newtask->img = $WhatsappTemplates->img;
        $newtask->msg = $WhatsappTemplates->msg;
        $newtask->template_name = $WhatsappCampain->template_name;
        $newtask->lead_id_type = $request->input('lead_id_type');
        $newtask->status = "0";
        if ($newtask->save()) {
            return response()->json([
                'success' => true,
                'message' => 'Task saved successfully',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save task',
            ]);
        }
    }

    public function get_whatsapp_doc($id)
    {
        if (env('TATA_WHATSAPP_MSG_STATUS') !== true) {
            return false;
        }
        $authKey = env('TATA_AUTH_KEY');
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://wb.omni.tatatelebusiness.com/whatsapp-cloud/media/download/$id",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $authKey",
            ],
        ]);
        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (curl_errno($curl)) {
            Log::error("Curl error: " . curl_error($curl));
            curl_close($curl);
            return null;
        }
        curl_close($curl);
        if ($httpcode >= 200 && $httpcode < 300) {
            $data = json_decode($response, true);
            Log::info("Media URL for message: " . $response);
            return $data['url'] ?? null;
        } else {
            Log::error("Request failed with HTTP status $httpcode and response: $response");
            return null;
        }
    }

    public function whatsapp_msg_get($id)
    {
        $perPage = 5;
        $messages = whatsappMessages::where('msg_from', $id)
            ->orderBy('time', 'desc')
            ->paginate($perPage);
        foreach ($messages as $message) {
            if (!empty($message->doc)) {
                $mediaUrl = $this->get_whatsapp_doc($message->doc);
                $message->doc = $mediaUrl;
            }
        }
        return response()->json($messages);
    }

    private function countVariables($text)
    {
        preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
        return count($matches[0]);
    }

    public function fetchTemplates(Request $request)
    {
        if (env('TATA_WHATSAPP_MSG_STATUS') !== true) {
            return false;
        }
        $url = 'https://wb.omni.tatatelebusiness.com/templates';
        $token = env("TATA_AUTH_KEY");
        $response = Http::withHeaders([
            'Authorization' => "Bearer $token",
        ])->get($url);

        if ($response->successful()) {
            $apiData = $response->json()['data'];
            $templates = collect($apiData);

            $data = $templates->map(function ($template) {
                $templateExists = WhatsappTemplates::where('template_name', $template['name'])->first();

                $headerVariableCount = 0;
                $bodyVariableCount = 0;
                foreach ($template['components'] as $component) {
                    if ($component['type'] === 'HEADER' && isset ($component['text'])) {
                        $headerVariableCount += $this->countVariables($component['text']);
                    } elseif ($component['type'] === 'BODY') {
                        $bodyVariableCount += $this->countVariables($component['text']);
                    }
                }

                $img = $template['components'][0]['example']['header_handle'][0] ?? null;
                $msg = $template['components'][1]['text'] ?? 'No message';

                if ($templateExists) {
                } else {
                    WhatsappTemplates::create([
                        'template_name' => $template['name'],
                        'is_variable_template' => $headerVariableCount > 0 || $bodyVariableCount > 0,
                        'img' => $img,
                        'msg' => $msg,
                        'status' => $template['status'],
                        'head_val' => $headerVariableCount,
                        'body_val' => $bodyVariableCount,
                        'last_updated_time' => $template['last_updated_time'],
                    ]);
                }
                return [
                    'id' => $template['id'],
                    'temp_name' => $template['name'],
                    'img' => $img,
                    'msg' => $msg,
                    'status' => $template['status'],
                    'created_at' => $template['last_updated_time'],
                ];
            });

            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => $templates->count(),
                'recordsFiltered' => $templates->count(),
                'data' => $data,
            ]);
        } else {
            return response()->json(['error' => 'Failed to fetch templates.'], 500);
        }
    }



    public function whatsapp_msg_get_new($id, Request $request)
    {
        $lastTimestamp = $request->input('lastTimestamp');

        $messages = whatsappMessages::where('msg_from', $id)
            ->where('time', '>', $lastTimestamp)
            ->orderBy('time', 'asc')
            ->get();

        foreach ($messages as $message) {
            if (!empty($message->doc)) {
                $mediaUrl = $this->get_whatsapp_doc($message->doc);
                $message->doc = $mediaUrl;
            }
        }

        return response()->json($messages);
    }



    public function whatsapp_msg_send(Request $request)
    {
        if (env('TATA_WHATSAPP_MSG_STATUS') !== true) {
            return false;
        }
        $url = "https://wb.omni.tatatelebusiness.com/whatsapp-cloud/messages";
        $authKey = env('TATA_AUTH_KEY');
        $response = Http::withHeaders([
            'Authorization' => "Bearer $authKey",
            'Content-Type' => 'application/json'
        ])->post($url, [
                    "messaging_product" => "whatsapp",
                    "recipient_type" => "individual",
                    "to" => "91$request->recipient",
                    "type" => "text",
                    "text" => [
                        "body" => "$request->message"
                    ]
                ]);
        if ($response->successful()) {
            Log::info($response);
            $currentTimestamp = Carbon::now();
            $newWaMsg = new whatsappMessages();
            $newWaMsg->msg_id = "$request->recipient";
            $newWaMsg->msg_from = "$request->recipient";
            $newWaMsg->time = $currentTimestamp;
            $newWaMsg->type = 'text';
            $newWaMsg->is_sent = "1";
            $newWaMsg->body = "$request->message";
            $newWaMsg->save();
            return response()->json(['message' => 'Message sent successfully.'], 200);
        } else {
            return response()->json(['error' => 'Failed to send message.'], $response->status());
        }
    }

    public function whatsapp_msg_send_multiple()
    {
        if (env('TATA_WHATSAPP_MSG_STATUS') !== true) {
            return false;
        }
        $messagesSent = 0;
        $maxMessages = 5;
        $token = env("TATA_AUTH_KEY");
        $url = "https://wb.omni.tatatelebusiness.com/whatsapp-cloud/messages";
        $authToken = "Bearer $token";
        $WhatsappBulkMsgTask = WhatsappBulkMsgTask::where('status', '0')->first();
        if (!$WhatsappBulkMsgTask) {
            echo "No task available.";
            return;
        }
        $loggedNumbers = WhatsappMsgLogs::where('task_id', $WhatsappBulkMsgTask->id)
            ->pluck('number')->toArray();
        $phoneNumbers = [];
        if ($WhatsappBulkMsgTask->lead_id_type == '1') {
            $leadIds = explode(',', $WhatsappBulkMsgTask->lead_ids);
            foreach ($leadIds as $leadid) {
                $lead = Lead::select('mobile')->where('lead_id', $leadid)->first();
                if (!in_array($lead->mobile, $loggedNumbers)) {
                    $phoneNumbers[] = $lead->mobile;
                }
            }
            print_r($phoneNumbers);
        } elseif ($WhatsappBulkMsgTask->lead_id_type == '2') {
            $leadIds = explode(',', $WhatsappBulkMsgTask->lead_ids);
            foreach ($leadIds as $leadid) {
                $lead = nvLead::select('mobile')->where('id', $leadid)->first();
                if (!in_array($lead->mobile, $loggedNumbers)) {
                    $phoneNumbers[] = $lead->mobile;
                }
            }
        } else {
            if (!empty($WhatsappBulkMsgTask->numbers)) {
                $tempNumbers = explode(',', $WhatsappBulkMsgTask->numbers);
                foreach ($tempNumbers as $number) {
                    if (!in_array(trim($number), $loggedNumbers)) {
                        $phoneNumbers[] = trim($number);
                    }
                }
            }
        }
        if (count($phoneNumbers) == 0) {
            $WhatsappBulkMsgTask->status = "1";
            $WhatsappBulkMsgTask->save();
        }
        foreach ($phoneNumbers as $phoneNumber) {
            if ($messagesSent >= $maxMessages) {
                break;
            }
            $messagesSent++;
            $phoneNumber = trim($phoneNumber);
            $getname = Lead::select('name')->where('mobile', $phoneNumber)->first();
            if (!$getname) {
                $getname = nvLead::select('name')->where('mobile', $phoneNumber)->first();
            }
            if (empty($getname->name)) {
                $getname->name = "sir/mam";
            }
            $response = Http::withHeaders([
                'Authorization' => $authToken,
                'Content-Type' => 'application/json',
            ])->post($url, [
                        "messaging_product" => "whatsapp",
                        "recipient_type" => "individual",
                        "to" => "91$phoneNumber",
                        "type" => "template",
                        "template" => [
                            "name" => "$WhatsappBulkMsgTask->template_name",
                            "language" => [
                                "code" => "en"
                            ],
                            "components" => [
                                [
                                    "type" => "header",
                                    "parameters" => [
                                        [
                                            "type" => "image",
                                            "image" => [
                                                "link" => "$WhatsappBulkMsgTask->img"
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    "type" => "body",
                                    "parameters" => [
                                        [
                                            "type" => "text",
                                            "text" => "$getname->name",
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]);
                    Log::info($response);
            $whatsappLogs = new WhatsappMsgLogs([
                'campaign_name' => $WhatsappBulkMsgTask->campaign_name,
                'task_id' => $WhatsappBulkMsgTask->id,
                'number' => $phoneNumber,
                'status' => $response->successful() ? '0' : '1',
            ]);
            $whatsappLogs->save();
        }
    }

    public function whatsapp_msg_status(Request $request)
    {
        $getlead = Lead::where('mobile', $request->mobile)->first();
        if ($getlead) {
            $getlead->is_whatsapp_msg = 0;
            $getlead->save();
        }
    }
    public function whatsapp_msg_status_nv_team(Request $request)
    {
        $getlead = nvrmLeadForward::where('mobile', $request->mobile)->first();
        if ($getlead) {
            $getlead->is_whatsapp_msg = 0;
            $getlead->save();
        }
    }
    public function whatsapp_msg_status_vendor(Request $request)
    {
        $getlead = nvLead::where('mobile', $request->mobile)->first();
        if ($getlead) {
            $getlead->is_whatsapp_msg = 0;
            $getlead->save();
        }
    }

}
