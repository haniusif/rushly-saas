<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Storage;

class WebhookController extends Controller
{

  


    public function webhook(Request $request)
    {


   Storage::disk('local')->append('log/'.date('Y-m-d').'/webhook.txt', json_encode($request->all()));

$data = $request->all();

   return response()->json([
            'success' => true,
            'message' => "Data received successfully",
            'data'    => $data,
        ], 200);
        
        
    }
    
    
 
}
