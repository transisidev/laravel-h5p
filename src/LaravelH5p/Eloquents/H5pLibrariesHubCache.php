<?php

namespace Transisi\LaravelH5P\Eloquents;

use Illuminate\Database\Eloquent\Model;

class H5pLibrariesHubCache extends Model
{
    protected $table = 'h5p_libraries_hub_cache';
    protected $primaryKey = 'id';
    protected $fillable = [
        'machine_name',
        'major_version',
        'minor_version',
        'patch_version',
        'h5p_major_version',
        'h5p_minor_version',
        'title',
        'summary',
        'description',
        'icon',
        'is_recommended',
        'popularity',
        'screenshots',
        'license',
        'example',
        'tutorial',
        'keywords',
        'categories',
        'owner',
        'created_at',
        'updated_at',
    ];
}
