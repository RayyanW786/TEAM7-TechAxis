<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContactMessage;

class ContactController extends Controller
{
    // Show the contact page
    public function index()
    {
        return view('contact');
    }

    // Handle form submit
    public function store(Request $request)
    {
        ContactMessage::create([
            'full_name' => $request->full_name,
            'email'     => $request->email,
            'subject'   => $request->subject,
            'message'   => $request->message,
        ]);

        return back()->with('success', 'Message sent successfully!');
    }
}

