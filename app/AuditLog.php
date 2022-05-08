<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Spatie\Permission\Models\Role;
use App\Traits\AuditableView;

class AuditLog extends Model
{
    use SoftDeletes, AuditableView;

    public $table = 'audit_logs';

    protected $fillable = [
        'action',
        'subject_id',
        'subject_type',
        'user_id',
        'properties',
        'before',
        'host',
        'year_id',
        'type_id',
        'level_id',
        'class_id',
        'segment_id', 
        'course_id',
        'created_at',
        'role_id', 
        'notes',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'properties' => 'collection',
        'before'     => 'collection',
        'year_id'    => 'array',
        'type_id'    => 'array',
        'level_id'   => 'array',
        'class_id'   => 'array',
        'segment_id' => 'array', 
        'course_id'  => 'array',
        'role_id'    => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

   /* public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }*/
}
