<?php

namespace App\Http\Controllers;

use App\Mail\ContactMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'name'    => 'required',
            'subject' => 'required',
            'email'   => 'required|email',
            'message' => 'required',
        ]);

        Mail::to(config('api.contact_email'))->send(new ContactMail([
            'name'    => $request->name,
            'email'   => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
        ]));

        return back()->with('success', 'Thanks for contacting us!');
    }
}
