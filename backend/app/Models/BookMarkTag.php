<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BookMarkTag extends Pivot
{
    protected $table = 'book_mark_tags';
}
