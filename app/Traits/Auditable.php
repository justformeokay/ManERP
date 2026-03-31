<?php

namespace App\Traits;

use App\Services\AuditLogService;

trait Auditable
{
    protected function getAuditModule(): string
    {
        return strtolower(class_basename($this->model ?? static::class));
    }

    public function logCreate($model, ?string $module = null): void
    {
        $mod = $module ?? $this->getAuditModule();
        AuditLogService::log(
            $mod,
            'create',
            ucfirst($mod) . " #{$model->id} created",
            null,
            $model->toArray()
        );
    }

    public function logUpdate($model, array $oldData, ?string $module = null): void
    {
        $mod = $module ?? $this->getAuditModule();
        AuditLogService::log(
            $mod,
            'update',
            ucfirst($mod) . " #{$model->id} updated",
            $oldData,
            $model->fresh()->toArray()
        );
    }

    public function logDelete($model, ?string $module = null): void
    {
        $mod = $module ?? $this->getAuditModule();
        AuditLogService::log(
            $mod,
            'delete',
            ucfirst($mod) . " #{$model->id} deleted",
            $model->toArray(),
            null
        );
    }

    public function logAction($model, string $action, ?string $description = null, ?array $oldData = null, ?string $module = null): void
    {
        $mod = $module ?? $this->getAuditModule();
        $desc = $description ?? ucfirst($mod) . " #{$model->id} {$action}";
        AuditLogService::log(
            $mod,
            $action,
            $desc,
            $oldData,
            $model->fresh()?->toArray()
        );
    }
}
