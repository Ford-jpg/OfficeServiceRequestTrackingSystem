<?php

namespace App\Models;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class ServiceRequest extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'Submitted';

    public const STATUS_UNDER_REVIEW = 'Under Review';

    public const STATUS_IN_PROGRESS = 'In Progress';

    public const STATUS_COMPLETED = 'Completed';

    public const STATUS_REJECTED = 'Rejected';

    public const STATUSES = [
        self::STATUS_SUBMITTED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_REJECTED,
    ];

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    public const PRIORITIES = [
        self::PRIORITY_LOW => 'Low',
        self::PRIORITY_MEDIUM => 'Medium',
        self::PRIORITY_HIGH => 'High',
        self::PRIORITY_URGENT => 'Urgent',
    ];

    protected $fillable = [
        'reference_number',
        'user_id',
        'department_id',
        'service_category_id',
        'description',
        'priority',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (ServiceRequest $request) {
            if (empty($request->reference_number)) {
                $prefix = 'REF-'.date('Ym').'-';
                $latest = self::where('reference_number', 'LIKE', $prefix.'%')
                    ->orderBy('id', 'desc')
                    ->value('reference_number');

                $nextNumber = 1;
                if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
                    $nextNumber = ((int) $matches[1]) + 1;
                }

                $request->reference_number = $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            }

            if (empty($request->user_id) && auth()->check()) {
                $request->user_id = auth()->id();
            }

            if (empty($request->status)) {
                $request->status = self::STATUS_SUBMITTED;
            }
        });

        static::created(function (ServiceRequest $request) {
            RequestAuditLog::create([
                'service_request_id' => $request->id,
                'user_id' => $request->user_id ?? auth()->id(),
                'action' => 'created',
                'from_status' => null,
                'to_status' => self::STATUS_SUBMITTED,
                'notes' => 'Request submitted',
                'created_at' => now(),
            ]);
        });
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(RequestAuditLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get valid next statuses based on workflow:
     * Submitted -> Under Review -> In Progress -> Completed/Rejected
     */
    public function getNextAllowedStatuses(?User $user = null): array
    {
        $user = $user ?? auth()->user();
        if (! $user || ! $user->canUpdateStatus()) {
            return [];
        }

        return match ($this->status) {
            self::STATUS_SUBMITTED => [self::STATUS_UNDER_REVIEW, self::STATUS_REJECTED],
            self::STATUS_UNDER_REVIEW => [self::STATUS_IN_PROGRESS, self::STATUS_REJECTED],
            self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED, self::STATUS_REJECTED],
            default => [],
        };
    }

    /**
     * Transition request status with authorization and audit trail logging.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function transitionTo(string $newStatus, User $user, ?string $notes = null): void
    {
        if (! $user->canUpdateStatus()) {
            throw new AuthorizationException('Only authorized personnel may update request status.');
        }

        $allowed = $this->getNextAllowedStatuses($user);
        if (! in_array($newStatus, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Invalid status transition from '{$this->status}' to '{$newStatus}'.",
            ]);
        }

        if ($newStatus === self::STATUS_REJECTED && empty(trim($notes ?? ''))) {
            throw ValidationException::withMessages([
                'notes' => 'A rejection reason is required when rejecting a request.',
            ]);
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;
        $this->save();

        RequestAuditLog::create([
            'service_request_id' => $this->id,
            'user_id' => $user->id,
            'action' => 'status_change',
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }
}
