<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessageReceived;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('pages.contact');
    }

    /**
     * Anfrage speichern und – falls konfiguriert – zusätzlich per Mail weiterleiten.
     */
    public function store(ContactRequest $request): RedirectResponse
    {
        if ($request->isSpam()) {
            return to_route('contact');
        }

        $message = Message::create($request->validated());

        if ($recipient = config('havoc.contact.mail_to')) {
            try {
                Mail::to($recipient)->send(new ContactMessageReceived($message));
            } catch (Throwable $exception) {
                // Die Anfrage ist gespeichert – ein Mailproblem soll den Besucher nicht blockieren.
                report($exception);
            }
        }

        return to_route('contact')->with('status', 'Danke! Deine Anfrage ist bei uns eingegangen – wir melden uns.');
    }
}
