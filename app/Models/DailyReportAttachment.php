<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DailyReportAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_report_id',
        'file_path',
    ];

    protected $appends = ['file_url'];

    public function dailyReport()
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function getFileUrlAttribute()
    {
        if ($this->file_path) {
            return url(Storage::url($this->file_path));
        }
        return null;
    }
}
