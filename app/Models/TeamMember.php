<?php

namespace App\Models;

use App\Models\Concerns\HasUploadedImage;
use Database\Factories\TeamMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('team')]
#[Fillable(['name', 'phone', 'email', 'hobbies', 'bio'])]
class TeamMember extends Model
{
    /** @use HasFactory<TeamMemberFactory> */
    use HasFactory, HasUploadedImage;

    protected function imageFolder(): string
    {
        return 'team';
    }
}
