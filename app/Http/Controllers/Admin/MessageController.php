<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MessageController extends Controller
{
    /**
     * Anfragen aus dem Kontaktformular, neueste zuerst.
     */
    public function index(): View
    {
        return view('admin.messages', [
            'messages' => Message::query()->latest('id')->get(),
        ]);
    }

    public function destroy(Message $message): RedirectResponse
    {
        $message->delete();

        return to_route('admin.messages.index')->with('status', 'Anfrage gelöscht //');
    }
}
