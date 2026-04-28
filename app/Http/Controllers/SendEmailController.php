<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendMail;

class SendEmailController extends Controller
{
    function send(Request $request)
    {
        $this->validate($request, [
            'name'     =>  'required',
            'subject'     =>  'required',
            'email'  =>  'required|email',
            'message' =>  'required'
        ]);

        $data = array(
            'name'      =>  $request->name,
            'email'     => $request->email,
            'subject'     => $request->subject,
            'message'   =>   $request->message
        );

        Mail::to('info@moviepickr.com')->send(new SendMail($data));
        return back()->with('success', 'Thanks for contacting us!');
    }
}
