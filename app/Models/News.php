<?php

namespace App\Models;

use App\Models\Concerns\HasUploadedImage;
use Database\Factories\NewsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'text'])]
class News extends Model
{
    /** @use HasFactory<NewsFactory> */
    use HasFactory, HasUploadedImage;

    protected function imageFolder(): string
    {
        return 'news';
    }
}
