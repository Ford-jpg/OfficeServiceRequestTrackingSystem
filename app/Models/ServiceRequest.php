<?php

namespace App\Models;

use App\Models\Scopes\DepartmentScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
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

    protected $fillable = [
        'ticket_number',
        'requester_id',
        'department_id',
        'service_category_id',
        'assigned_to_user_id',
        'title',
        'description',
        'priority',
        'status',
        'rejection_reason',
        'resolution_notes',
        'due_date',
        'resolved_at',
        'satisfaction_rating',
        'satisfaction_feedback',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'due_date' => 'datetime',
            'resolved_at' => 'datetime',
            'satisfaction_rating' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new DepartmentScope);

        static::creating(function (ServiceRequest $request) {
            if (empty($request->ticket_number)) {
                $prefix = 'SR-'.date('Ym').'-';
                $latest = self::withoutGlobalScopes()->where('ticket_number', 'LIKE', $prefix.'%')
                    ->orderBy('id', 'desc')
                    ->value('ticket_number');

                $nextNumber = 1;
                if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
                    $nextNumber = ((int) $matches[1]) + 1;
                }

                $request->ticket_number = $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            }

            if (empty($request->department_id) && $request->service_category_id) {
                $category = ServiceCategory::find($request->service_category_id);
                if ($category) {
                    $request->department_id = $category->department_id;
                }
            }

            if (empty($request->status)) {
                $request->status = self::STATUS_SUBMITTED;
            }
        });

        static::created(function (ServiceRequest $request) {
            RequestAuditLog::create([
                'service_request_id' => $request->id,
                'user_id' => $request->requester_id ?? auth()->id(),
                'action' => 'created',
                'from_status' => null,
                'to_status' => self::STATUS_SUBMITTED,
                'notes' => 'Request submitted by requester',
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
            ]);
        });
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(RequestComment::class)->orderBy('created_at', 'desc');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(RequestAuditLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get valid next statuses based on the required workflow:
     * Submitted -> Under Review -> In Progress -> Completed/Rejected
     */
    public function getNextAllowedStatuses(?User $user = null): array
    {
        $user = $user ?? auth()->user();
        if (! $user || ! $user->canUpdateStatus()) {
            return [];
        }

        if ($user->isAdmin()) {
            return match ($this->status) {
                self::STATUS_SUBMITTED => [self::STATUS_UNDER_REVIEW, self::STATUS_REJECTED],
                self::STATUS_UNDER_REVIEW => [self::STATUS_IN_PROGRESS, self::STATUS_REJECTED, self::STATUS_SUBMITTED],
                self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED, self::STATUS_REJECTED, self::STATUS_UNDER_REVIEW],
                self::STATUS_COMPLETED, self::STATUS_REJECTED => [self::STATUS_UNDER_REVIEW, self::STATUS_IN_PROGRESS],
                default => [self::STATUS_SUBMITTED],
            };
        }

        return match ($this->status) {
            self::STATUS_SUBMITTED => [self::STATUS_UNDER_REVIEW, self::STATUS_REJECTED],
            self::STATUS_UNDER_REVIEW => [self::STATUS_IN_PROGRESS, self::STATUS_REJECTED],
            self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED, self::STATUS_REJECTED],
            default => [],
        };
    }

    /**
     * Execute status transition with authorization, validation, and audit recording.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function transitionTo(string $newStatus, User $user, ?string $notes = null): void
    {
        if (! $user->canUpdateStatus()) {
            throw new AuthorizationException('You are not authorized to update request statuses.');
        }

        if (! in_array($newStatus, self::STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => "Invalid status: {$newStatus}",
            ]);
        }

        if ($this->status === $newStatus) {
            return;
        }

        $allowed = $this->getNextAllowedStatuses($user);
        if (! in_array($newStatus, $allowed, true) && ! $user->isAdmin()) {
            throw ValidationException::withMessages([
                'status' => "Invalid status transition from '{$this->status}' to '{$newStatus}'.",
            ]);
        }

        if ($newStatus === self::STATUS_REJECTED && empty(trim($notes ?? ''))) {
            throw ValidationException::withMessages([
                'rejection_reason' => 'A reason is required when rejecting a request.',
            ]);
        }

        if ($newStatus === self::STATUS_COMPLETED && empty(trim($notes ?? ''))) {
            throw ValidationException::withMessages([
                'resolution_notes' => 'Resolution notes are required when marking a request as Completed.',
            ]);
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;

        if ($newStatus === self::STATUS_REJECTED) {
            $this->rejection_reason = $notes;
        } elseif ($newStatus === self::STATUS_COMPLETED) {
            $this->resolution_notes = $notes;
            $this->resolved_at = Carbon::now();
        }

        $this->save();

        RequestAuditLog::create([
            'service_request_id' => $this->id,
            'user_id' => $user->id,
            'action' => 'status_change',
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'notes' => $notes,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
