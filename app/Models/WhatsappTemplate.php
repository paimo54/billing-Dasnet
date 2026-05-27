<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'message',
        'is_active',
        'variables',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'variables' => 'array',
    ];

    /**
     * Get messages sent using this template
     */
    public function messages()
    {
        return $this->hasMany(WhatsappMessage::class, 'template_id');
    }

    /**
     * Replace variables in template message
     */
    public function render(array $data): string
    {
        $message = $this->message;

        foreach ($data as $key => $value) {
            $message = str_replace('{{' . $key . '}}', $value, $message);
        }

        return $message;
    }

    /**
     * Get available variables from message
     */
    public function getAvailableVariables(): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $this->message, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Scope: Active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
