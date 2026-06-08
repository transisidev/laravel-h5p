<?php

namespace Transisi\LaravelH5P\Eloquents;

use Illuminate\Database\Eloquent\Model;

class H5pResult extends Model
{
    protected $primaryKey = 'id';
    protected $fillable = [
        'content_id',
        'user_id',
        'score',
        'max_score',
        'opened',
        'finished',
        'time',
    ];
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function content()
    {
        return $this->belongsTo(H5pContent::class, 'content_id');
    }
}
