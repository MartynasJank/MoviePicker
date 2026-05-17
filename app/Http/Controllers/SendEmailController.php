<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendMail;

class SendEmailController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'name'    => 'required',
            'subject' => 'required',
            'email'   => 'required|email',
            'message' => 'required',
        ]);

        Mail::to(env('CONTACT_EMAIL', 'info@moviepickr.com'))->send(new SendMail([
            'name'    => $request->name,
            'email'   => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
        ]));

        return back()->with('success', 'Thanks for contacting us!');
    }
}
