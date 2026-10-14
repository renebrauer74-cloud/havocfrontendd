<?php

namespace App\Models;

use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Anfrage aus dem Kontaktformular.
 */
#[Fillable(['name', 'email', 'message'])]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;
}
